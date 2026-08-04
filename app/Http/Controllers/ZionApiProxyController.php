<?php

namespace App\Http\Controllers;

use App\Services\ZionShippingApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ZionApiProxyController extends Controller
{
    public function __construct(private readonly ZionShippingApi $zion)
    {
    }

    public function login(Request $request): JsonResponse|RedirectResponse|Response
    {
        $payload = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role_id' => ['nullable', 'integer'],
        ]);

        $response = $this->zion->post('kay-paolo/login', array_filter($payload, static function ($value) {
            return $value !== null && $value !== '';
        }));

        $data = $response['data'] ?? [];
        $failed = !$response['ok']
            || (($data['error'] ?? 'false') === 'true')
            || empty($data['access_token']);

        if (!$failed && $request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->put([
                'zion.access_token' => $data['access_token'],
                'zion.token_type' => $data['token_type'] ?? 'Bearer',
                'zion.user' => $data['user'] ?? [],
            ]);
        }

        if ($request->expectsJson()) {
            return $this->jsonResponse($response);
        }

        if ($failed) {
            return redirect()->route('login', ['login_error' => $data['message'] ?? 'Unable to log in to Kay Paolo.']);
        }

        return response($this->browserRedirectScript($data))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function fetchUserForQuote(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/fetch-user-for-quote', $request);
    }

    public function consigneeList(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/consignee-list', $request);
    }

    public function countries(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $response = $this->zion->get('kay-paolo/countries', $request->query(), $token);

        if (!$response['ok']) {
            $response = $this->zion->get('countries', $request->query(), $token);
        }

        if (!$response['ok']) {
            return response()->json([
                'status' => 'error',
                'message' => $response['data']['message'] ?? 'Unable to load countries from the shipping API.',
                'countries' => [],
            ], $response['status'] > 0 ? $response['status'] : 502);
        }

        return response()->json([
            'status' => 'success',
            'countries' => $this->normalizeCountries($response['data']),
        ]);
    }

    public function paymentOptions(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $response = $this->zion->get('kay-paolo/payment-options', [], $token);

        if (!$response['ok']) {
            $response = $this->zion->get('payment-options', [], $token);
        }

        $options = $response['ok']
            ? $this->normalizePaymentOptions($response['data'])
            : [];

        if (empty($options)) {
            $options = $this->defaultPaymentOptions();
        }

        return response()->json([
            'status' => 'success',
            'options' => $options,
            'source' => $response['ok'] ? 'api' : 'fallback',
        ]);
    }

    public function flatRates(Request $request): JsonResponse
    {
        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'kay-paolo/get-flat-rates'],
            ['endpoint' => 'web-api/get-flat-rates-bocicot', 'web' => true],
        ], $request);
    }

    public function saveConsignee(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/save-consignee', $request);
    }

    public function quote(Request $request): JsonResponse
    {
        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'kay-paolo/get-quote-result'],
            ['endpoint' => 'web-api/get-quote-result-bocicot', 'web' => true],
        ], $request);
    }

    public function createShipment(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please login to Kay Paolo first.',
            ], 401);
        }

        $payload = $this->sanitizeShipmentPayload($request->except('_token'));
        $response = $this->zion->post('kay-paolo/update-shipping', $payload, $token);

        if (!$response['ok'] && $this->shouldTryFallback($response)) {
            $response = $this->zion->postWeb('web-api/update-shipping-bocicot', $payload, $token);
        }

        if ($this->isRecoverableZionAccountNumberSchemaError($response)) {
            $retryResponse = $this->zion->postWeb('web-api/update-shipping-bocicot', $payload, $token);

            if (!$this->isRecoverableZionAccountNumberSchemaError($retryResponse)) {
                return $this->jsonResponse($retryResponse);
            }

            $response = $retryResponse;
        }

        return $this->jsonResponse($response);
    }

    public function shippingHistory(Request $request): JsonResponse
    {
        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'kay-paolo/shipping-history-filter'],
            ['endpoint' => 'bocicot/shipping-history-filter'],
            ['endpoint' => 'web-api/shipping-history-filter-bocicot', 'web' => true],
        ], $request);
    }

    public function tracking(Request $request): JsonResponse
    {
        return $this->forward('kay-paolo/validate-tracking', $request);
    }

    public function emailShipment(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'shipment_id' => ['nullable'],
            'shipping_id' => ['nullable'],
            'id' => ['nullable'],
        ]);

        $token = $request->bearerToken();
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please login to Kay Paolo first.',
            ], 401);
        }

        $shipmentId = $request->input('shipment_id', $request->input('shipping_id', $request->input('id')));
        $response = $this->zion->post('kay-paolo/email-shipment', $request->except('_token'), $token);

        if (!$response['ok'] && $shipmentId) {
            $response = $this->zion->postWeb('email-shipment/'.urlencode((string) $shipmentId), [
                'email' => $request->input('email'),
            ], $token);
        }

        return $this->jsonResponse($response);
    }

    public function shipmentLabel(Request $request): RedirectResponse
    {
        return redirect()->away($this->documentUrl($request, 'label'));
    }

    public function shipmentReceipt(Request $request): RedirectResponse
    {
        return redirect()->away($this->documentUrl($request, 'receipt'));
    }

    private function forwardAuthenticated(string $endpoint, Request $request, ?array $payload = null): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please login to Kay Paolo first.',
            ], 401);
        }

        return $this->forward($endpoint, $request, $token, $payload);
    }

    private function forward(string $endpoint, Request $request, ?string $token = null, ?array $payload = null): JsonResponse
    {
        $response = $this->zion->post($endpoint, $payload ?? $request->except('_token'), $token);

        return $this->jsonResponse($response);
    }

    private function forwardAuthenticatedWithFallback(array $targets, Request $request, ?array $payload = null): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please login to Kay Paolo first.',
            ], 401);
        }

        $lastResponse = null;
        foreach ($targets as $target) {
            $endpoint = $target['endpoint'];
            $lastResponse = !empty($target['web'])
                ? $this->zion->postWeb($endpoint, $payload ?? $request->except('_token'), $token)
                : $this->zion->post($endpoint, $payload ?? $request->except('_token'), $token);

            if ($lastResponse['ok']) {
                return $this->jsonResponse($lastResponse);
            }

            if (!$this->shouldTryFallback($lastResponse)) {
                return $this->jsonResponse($lastResponse);
            }
        }

        return $this->jsonResponse($lastResponse ?? [
            'ok' => false,
            'status' => 502,
            'data' => [
                'status' => 'error',
                'message' => 'Unable to reach the shipping API.',
            ],
        ]);
    }

    private function sanitizeShipmentPayload(array $payload): array
    {
        $dimensions = $this->normalizeShipmentDimensions($payload);
        $rowCount = $this->dimensionRowCount($dimensions);
        $flatRate = $this->padArray($payload['flat_rate'] ?? [], $rowCount, '0');
        $shipmentType = $this->padArray($payload['shipment_type'] ?? [], $rowCount, '');
        $deliveryLocation = $this->normalizeDeliveryLocation($payload['delivery_location'] ?? $payload['deliveryLocation'] ?? '');
        $selectedShipper = $payload['selected_shipper'] ?? $payload['delivery_option'] ?? null;
        $declaredValue = $this->positiveNumber($payload['total_value'] ?? $payload['package_value'] ?? null, 10);
        $fragileShipment = $payload['is_fragile_shipment'] ?? $payload['fragile_shipment'] ?? 0;

        return $this->compactPayload([
            'user_id' => $payload['user_id'] ?? $payload['quote_user_id'] ?? null,
            'quote_user_id' => $payload['quote_user_id'] ?? $payload['user_id'] ?? null,
            'quote_id' => $payload['quote_id'] ?? null,
            'partner' => $this->normalizePartner($payload['partner'] ?? 'ZION'),
            'selected_shipper' => $selectedShipper,
            'delivery_option' => $selectedShipper,
            'from_name' => $payload['from_name'] ?? null,
            'from_email' => $payload['from_email'] ?? null,
            'from_phone' => $payload['from_phone'] ?? null,
            'from_country_name' => $payload['from_country_name'] ?? null,
            'from_country' => $payload['from_country'] ?? null,
            'from_address' => $payload['from_address'] ?? null,
            'from_apt' => $payload['from_apt'] ?? '',
            'from_zip' => $payload['from_zip'] ?? null,
            'from_city' => $payload['from_city'] ?? null,
            'from_state' => $payload['from_state'] ?? null,
            'consignee_id' => $payload['consignee_id'] ?? $payload['consignees_id'] ?? null,
            'consignees_id' => $payload['consignees_id'] ?? $payload['consignee_id'] ?? null,
            'consignee_name' => $payload['consignee_name'] ?? $payload['to_name'] ?? null,
            'consignee_phone' => $payload['consignee_phone'] ?? $payload['to_phone_1'] ?? null,
            'consignee_homephone' => $payload['consignee_homephone'] ?? $payload['to_phone_2'] ?? '',
            'to_name' => $payload['to_name'] ?? $payload['consignee_name'] ?? null,
            'to_phone_1' => $payload['to_phone_1'] ?? $payload['consignee_phone'] ?? null,
            'to_phone_2' => $payload['to_phone_2'] ?? $payload['consignee_homephone'] ?? '',
            'to_country_name' => $payload['to_country_name'] ?? null,
            'to_country' => $payload['to_country'] ?? null,
            'to_address' => $payload['to_address'] ?? null,
            'to_apt' => $payload['to_apt'] ?? '',
            'to_zip' => $payload['to_zip'] ?? null,
            'to_city' => $payload['to_city'] ?? null,
            'to_state' => $payload['to_state'] ?? null,
            'package_count' => $rowCount,
            'package_description' => $payload['package_description'] ?? '',
            'total_value' => $declaredValue,
            'package_value' => $declaredValue,
            'dimensions' => $dimensions,
            'flat_rate' => $flatRate,
            'shipment_type' => $shipmentType,
            'packages' => $this->expandedPackages($dimensions, $flatRate, $shipmentType),
            'monetaryAmount' => [[
                'typeCode' => 'declaredValue',
                'value' => $declaredValue,
                'currency' => 'USD',
            ]],
            'plannedShippingDateAndTime' => $payload['plannedShippingDateAndTime'] ?? $this->plannedShippingDateTime(),
            'delivery_location' => $deliveryLocation,
            'deliveryLocation' => $deliveryLocation,
            'delivery_description' => $payload['delivery_description'] ?? '',
            'payment_type' => $payload['payment_type'] ?? 'PAID AT AGENT',
            'deliveryEstimatePrice' => $payload['deliveryEstimatePrice'] ?? null,
            'deliveryEstimateDate' => $payload['deliveryEstimateDate'] ?? null,
            'promo' => $payload['promo'] ?? $payload['coupon_code'] ?? '',
            'coupon_code' => $payload['coupon_code'] ?? $payload['promo'] ?? '',
            'extra_service_charge' => $payload['extra_service_charge'] ?? '',
            'flaterateinside' => $this->hasFlatRate($flatRate, $shipmentType) ? 1 : 0,
            'fragile_shipment' => $fragileShipment,
            'is_fragile_shipment' => $fragileShipment,
        ]);
    }

    private function normalizeShipmentDimensions(array $payload): array
    {
        $rawDimensions = $payload['dimensions'] ?? [];
        if (!is_array($rawDimensions)) {
            $rawDimensions = [];
        }

        $counts = $this->arrayValue($rawDimensions['package_count_ind'] ?? ($payload['package_count_ind'] ?? 1));
        $weights = $this->arrayValue($rawDimensions['weight'] ?? ($payload['package_weight'] ?? 1));
        $lengths = $this->arrayValue($rawDimensions['length'] ?? ($payload['package_length'] ?? 1));
        $widths = $this->arrayValue($rawDimensions['width'] ?? ($payload['package_width'] ?? 1));
        $heights = $this->arrayValue($rawDimensions['height'] ?? ($payload['package_height'] ?? 1));

        $rowCount = max(count($counts), count($weights), count($lengths), count($widths), count($heights), 1);

        return [
            'package_count_ind' => array_map(fn ($value) => $this->positiveNumber($value, 1), $this->padArray($counts, $rowCount, 1)),
            'weight' => array_map(fn ($value) => $this->positiveNumber($value, 1), $this->padArray($weights, $rowCount, 1)),
            'length' => array_map(fn ($value) => $this->positiveNumber($value, 1), $this->padArray($lengths, $rowCount, 1)),
            'width' => array_map(fn ($value) => $this->positiveNumber($value, 1), $this->padArray($widths, $rowCount, 1)),
            'height' => array_map(fn ($value) => $this->positiveNumber($value, 1), $this->padArray($heights, $rowCount, 1)),
        ];
    }

    private function expandedPackages(array $dimensions, array $flatRate, array $shipmentType): array
    {
        $packages = [];
        $rowCount = $this->dimensionRowCount($dimensions);

        for ($index = 0; $index < $rowCount; $index++) {
            $repeat = (int) $this->positiveNumber($dimensions['package_count_ind'][$index] ?? 1, 1);
            $currentShipmentType = trim((string) ($shipmentType[$index] ?? ''));
            $isFlatRate = $this->flatRateIsOn($flatRate[$index] ?? null) || $currentShipmentType !== '';

            for ($piece = 0; $piece < $repeat; $piece++) {
                $package = [
                    'typeCode' => $currentShipmentType === 'contains_document' ? '2BP' : '3BX',
                    'weight' => $this->positiveNumber($dimensions['weight'][$index] ?? 0, 0),
                    'dimensions' => [
                        'length' => $this->positiveNumber($dimensions['length'][$index] ?? 0, 0),
                        'width' => $this->positiveNumber($dimensions['width'][$index] ?? 0, 0),
                        'height' => $this->positiveNumber($dimensions['height'][$index] ?? 0, 0),
                    ],
                ];

                if ($isFlatRate && $currentShipmentType !== '') {
                    $package['flat_rate_type'] = $currentShipmentType;
                }

                $packages[] = $package;
            }
        }

        return $packages;
    }

    private function dimensionRowCount(array $dimensions): int
    {
        return max(
            count($dimensions['package_count_ind'] ?? []),
            count($dimensions['weight'] ?? []),
            count($dimensions['length'] ?? []),
            count($dimensions['width'] ?? []),
            count($dimensions['height'] ?? []),
            1
        );
    }

    private function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null || $value === '') {
            return [];
        }

        return [$value];
    }

    private function padArray(mixed $value, int $count, mixed $fallback): array
    {
        $values = array_slice($this->arrayValue($value), 0, $count);

        while (count($values) < $count) {
            $values[] = $fallback;
        }

        return array_values($values);
    }

    private function positiveNumber(mixed $value, float|int $fallback): float|int
    {
        $number = is_numeric($value)
            ? (float) $value
            : (float) preg_replace('/[^0-9.-]/', '', (string) $value);

        return $number > 0 ? $number : $fallback;
    }

    private function normalizeDeliveryLocation(mixed $value): string
    {
        $raw = trim((string) $value);
        $lower = strtolower($raw);

        if ($lower === '') {
            return '';
        }

        if (str_contains($lower, 'office') || str_contains($lower, 'pickup')) {
            return 'Pickup in Office';
        }

        if (str_contains($lower, 'home')) {
            return 'Home Delivery';
        }

        return $raw;
    }

    private function normalizePartner(mixed $value): string
    {
        $raw = strtolower((string) ($value ?: 'zion'));

        if (str_contains($raw, 'ups')) {
            return 'UPS';
        }

        if (str_contains($raw, 'fedex')) {
            return 'FEDEX';
        }

        if (str_contains($raw, 'usps')) {
            return 'USPS';
        }

        if (str_contains($raw, 'dhl')) {
            return 'DHL';
        }

        return 'ZION';
    }

    private function plannedShippingDateTime(): string
    {
        $timestamp = time();
        $day = date('D', $timestamp);

        if ($day === 'Sat') {
            $timestamp = strtotime('+2 days', $timestamp);
        } elseif ($day === 'Sun') {
            $timestamp = strtotime('+1 day', $timestamp);
        }

        return date('Y-m-d', $timestamp).'T00:00:00';
    }

    private function hasFlatRate(array $flatRate, array $shipmentType): bool
    {
        foreach ($flatRate as $value) {
            if ($this->flatRateIsOn($value)) {
                return true;
            }
        }

        foreach ($shipmentType as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function flatRateIsOn(mixed $value): bool
    {
        return $value === true
            || $value === 1
            || in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function compactPayload(array $payload): array
    {
        return array_filter($payload, static fn ($value) => $value !== null);
    }

    private function isRecoverableZionAccountNumberSchemaError(array $response): bool
    {
        $message = strtolower((string) ($response['data']['message'] ?? ''));

        return (int) ($response['status'] ?? 0) >= 500
            && str_contains($message, 'unknown column')
            && str_contains($message, 'account_number')
            && str_contains($message, 'shippings');
    }

    private function shouldTryFallback(array $response): bool
    {
        $status = (int) ($response['status'] ?? 0);
        $message = strtolower((string) ($response['data']['message'] ?? ''));

        return in_array($status, [404, 405], true)
            || ($status >= 500 && str_contains($message, 'session store not set'));
    }

    private function normalizeCountries(array $payload): array
    {
        $rows = $payload['countries']
            ?? $payload['data']['data']
            ?? $payload['data']
            ?? [];

        if (!is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($country) {
                if (!is_array($country)) {
                    return null;
                }

                $code = strtoupper(trim((string) ($country['alpha_2_code'] ?? $country['code'] ?? $country['value'] ?? '')));
                $name = trim((string) ($country['country_name'] ?? $country['name'] ?? $country['label'] ?? ''));

                if ($code === '' || $name === '') {
                    return null;
                }

                return [
                    'id' => $country['id'] ?? null,
                    'code' => $code,
                    'name' => $name,
                    'dial_code' => $country['dial_code'] ?? null,
                    'zip_code_supported' => $country['zip_code_supported'] ?? null,
                    'flat_rate_supported' => $country['flatrate_support'] ?? $country['flat_rate_supported'] ?? null,
                ];
            })
            ->filter()
            ->sortBy('name')
            ->values()
            ->all();
    }

    private function normalizePaymentOptions(array $payload): array
    {
        $rows = $payload['options']
            ?? $payload['payment_options']
            ?? $payload['data']['options']
            ?? $payload['data']
            ?? [];

        if (!is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($option) {
                if (is_string($option)) {
                    return [
                        'value' => $option,
                        'label' => $this->paymentLabel($option),
                    ];
                }

                if (!is_array($option)) {
                    return null;
                }

                $value = trim((string) ($option['value'] ?? $option['code'] ?? $option['type'] ?? $option['name'] ?? ''));
                if ($value === '') {
                    return null;
                }

                return [
                    'value' => $value,
                    'label' => trim((string) ($option['label'] ?? $option['name'] ?? $this->paymentLabel($value))),
                ];
            })
            ->filter()
            ->unique('value')
            ->values()
            ->all();
    }

    private function defaultPaymentOptions(): array
    {
        return collect(['PAID AT AGENT', 'COLLECT', 'CREDIT OR DEBIT CARD', 'PAYPAL', 'SQUARE', 'SPLIT', 'PARTIAL PAYMENT'])
            ->map(fn ($value) => [
                'value' => $value,
                'label' => $this->paymentLabel($value),
            ])
            ->all();
    }

    private function paymentLabel(string $value): string
    {
        return match ($value) {
            'PAID AT AGENT' => 'Paid at Store',
            'COLLECT' => 'Collect',
            'CREDIT OR DEBIT CARD' => 'Credit or Debit Card',
            'PAYPAL' => 'PayPal',
            'SQUARE' => 'Square',
            'SPLIT' => 'Split Payment',
            'PARTIAL PAYMENT' => 'Partial Payment',
            default => ucwords(strtolower(str_replace(['_', '-'], ' ', $value))),
        };
    }

    private function documentUrl(Request $request, string $type): string
    {
        $shipmentId = trim((string) $request->query('shipment_id', $request->query('shipping_id', '')));
        $invoice = trim((string) $request->query('invoice', $request->query('id', '')));

        if ($shipmentId !== '' && ctype_digit($shipmentId)) {
            return $this->zion->webUrl($type === 'label'
                ? 'get_shipping_label/'.$shipmentId
                : 'get_shipping_receipt/'.$shipmentId);
        }

        $cleanInvoice = preg_replace('/[^A-Za-z0-9_-]/', '', $invoice);
        if ($cleanInvoice !== '') {
            return $this->zion->webUrl($type === 'label'
                ? 'label/label_'.$cleanInvoice.'.pdf'
                : 'receipt/receipt_'.$cleanInvoice.'.pdf');
        }

        return $this->zion->webUrl('/');
    }

    private function jsonResponse(array $response): JsonResponse
    {
        $status = $response['status'] > 0 ? $response['status'] : 502;

        return response()->json($response['data'], $status);
    }

    private function browserRedirectScript(array $data): string
    {
        $token = json_encode($data['access_token'] ?? '');
        $user = json_encode($data['user'] ?? []);
        $home = json_encode(route('home'));

        return <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Redirecting...</title></head>
<body>
<script>
window.localStorage.setItem('kayPaoloZionToken', {$token});
window.localStorage.setItem('kayPaoloZionUser', JSON.stringify({$user}));
window.location.replace({$home});
</script>
</body>
</html>
HTML;
    }
}
