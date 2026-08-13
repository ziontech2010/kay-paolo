<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmShipmentMail;
use App\Services\ShipmentDocumentPdfService;
use App\Services\ZionShippingApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ZionApiProxyController extends Controller
{
    public function __construct(
        private readonly ZionShippingApi $zion,
        private readonly ShipmentDocumentPdfService $documents
    ) {
    }

    public function login(Request $request): JsonResponse|RedirectResponse|Response
    {
        $payload = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $cleanPayload = array_filter($payload, static function ($value) {
            return $value !== null && $value !== '';
        });

        $response = $this->zion->post('bocicot/login', $cleanPayload);

        if (!$response['ok'] && $this->shouldTryFallback($response)) {
            $response = $this->zion->post('kay-paolo/login', $cleanPayload);
        }

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
        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'web-api/fetch-user-for-quote-bocicot', 'web' => true],
            ['endpoint' => 'bocicot/fetch-user-for-quote'],
            ['endpoint' => 'kay-paolo/fetch-user-for-quote'],
        ], $request);
    }

    public function consigneeList(Request $request): JsonResponse
    {
        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'bocicot/consignee-list'],
            ['endpoint' => 'web-api/consignee-list-bocicot', 'web' => true],
            ['endpoint' => 'web-api/fetch-consignee-for-quote-bocicot', 'web' => true],
            ['endpoint' => 'kay-paolo/consignee-list'],
        ], $request);
    }

    public function countries(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $lastResponse = null;

        foreach ([
            ['endpoint' => 'bocicot/countries'],
            ['endpoint' => 'web-api/countries-bocicot', 'web' => true],
            ['endpoint' => 'kay-paolo/countries'],
            ['endpoint' => 'countries'],
        ] as $target) {
            $response = !empty($target['web'])
                ? $this->zion->getWeb($target['endpoint'], $request->query(), $token)
                : $this->zion->get($target['endpoint'], $request->query(), $token);
            $lastResponse = $response;
            $countries = $response['ok'] ? $this->normalizeCountries($response['data']) : [];

            if ($response['ok'] && !empty($countries)) {
                return response()->json([
                    'status' => 'success',
                    'countries' => $countries,
                ]);
            }

            if (!$response['ok'] && !$this->shouldTryFallback($response)) {
                break;
            }
        }

        $response = $lastResponse ?? [
            'ok' => false,
            'status' => 502,
            'data' => [],
        ];

        if (!$response['ok']) {
            return response()->json([
                'status' => 'error',
                'message' => $response['data']['message'] ?? 'Unable to load countries from the shipping API.',
                'countries' => [],
            ], $response['status'] > 0 ? $response['status'] : 502);
        }

        return response()->json([
            'status' => 'success',
            'countries' => [],
        ]);
    }

    public function paymentOptions(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $emptySuccess = null;

        foreach ([
            ['endpoint' => 'bocicot/payment-options'],
            ['endpoint' => 'web-api/payment-options-bocicot', 'web' => true],
            ['endpoint' => 'web-api/get-payment-options-bocicot', 'web' => true],
            ['endpoint' => 'kay-paolo/payment-options'],
        ] as $target) {
            $response = !empty($target['web'])
                ? $this->zion->getWeb($target['endpoint'], $request->query(), $token)
                : $this->zion->get($target['endpoint'], $request->query(), $token);
            $options = $response['ok']
                ? $this->normalizePaymentOptions($response['data'])
                : [];

            if ($response['ok'] && !empty($options)) {
                return response()->json([
                    'status' => 'success',
                    'options' => $options,
                    'source' => 'api',
                ]);
            }

            if ($response['ok']) {
                $emptySuccess = $response;
                continue;
            }

            if (!$this->shouldTryFallback($response)) {
                return response()->json([
                    'status' => 'error',
                    'message' => $response['data']['message'] ?? 'Unable to load payment options from the shipping API.',
                    'options' => [],
                    'source' => 'api',
                ], $response['status'] > 0 ? $response['status'] : 502);
            }
        }

        $options = $emptySuccess
            ? $this->normalizePaymentOptions($emptySuccess['data'])
            : [];

        return response()->json([
            'status' => 'success',
            'options' => $options,
            'source' => 'api',
        ]);
    }

    public function flatRates(Request $request): JsonResponse
    {
        $payload = $this->sanitizeFlatRatePayload($request->except('_token'));

        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'web-api/get-flat-rates-bocicot', 'web' => true],
            ['endpoint' => 'bocicot/get-flat-rates'],
            ['endpoint' => 'kay-paolo/get-flat-rates'],
        ], $request, $payload);
    }

    public function saveConsignee(Request $request): JsonResponse
    {
        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'bocicot/save-consignee'],
            ['endpoint' => 'web-api/save-consignee-bocicot', 'web' => true],
            ['endpoint' => 'kay-paolo/save-consignee'],
        ], $request);
    }

    public function quote(Request $request): JsonResponse
    {
        $payload = $this->sanitizeQuotePayload($request->except('_token'));

        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'web-api/get-quote-result-bocicot', 'web' => true],
            ['endpoint' => 'bocicot/get-quote-result'],
            ['endpoint' => 'kay-paolo/get-quote-result'],
        ], $request, $payload);
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

        $rawPayload = $request->except('_token');
        $payload = $this->sanitizeShipmentPayload($rawPayload);
        $documentPayload = array_merge($rawPayload, $payload);
        $response = $this->postWithFallback([
            ['endpoint' => 'web-api/update-shipping-bocicot', 'web' => true],
            ['endpoint' => 'bocicot/update-shipping'],
            ['endpoint' => 'kay-paolo/update-shipping'],
        ], $payload, $token);

        if ($this->isRecoverableZionAccountNumberSchemaError($response)) {
            $retryResponse = $this->zion->postWeb('web-api/update-shipping-bocicot', $payload, $token);

            if (!$this->isRecoverableZionAccountNumberSchemaError($retryResponse)) {
                $this->attachDocumentContextKey(
                    $retryResponse,
                    $this->rememberShipmentContext($request, $retryResponse['data'] ?? [], $documentPayload)
                );
                $this->attachShipmentEmailResult($request, $retryResponse, $documentPayload);

                return $this->jsonResponse($retryResponse);
            }

            $response = $retryResponse;
        }

        if ($response['ok'] ?? false) {
            $this->attachDocumentContextKey(
                $response,
                $this->rememberShipmentContext($request, $response['data'] ?? [], $documentPayload)
            );
            $this->attachShipmentEmailResult($request, $response, $documentPayload);
        }

        return $this->jsonResponse($response);
    }

    public function storeShipmentDocumentContext(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'response' => ['nullable', 'array'],
            'payload' => ['nullable', 'array'],
            'selected' => ['nullable', 'array'],
            'context_key' => ['nullable', 'string', 'max:80'],
            'document_context_key' => ['nullable', 'string', 'max:80'],
        ]);

        $contextKey = $this->storeShipmentContext(
            $payload['response'] ?? [],
            $payload['payload'] ?? [],
            $payload['selected'] ?? [],
            $payload['context_key'] ?? $payload['document_context_key'] ?? null
        );

        return response()->json(array_filter([
            'status' => 'success',
            'document_context_key' => $contextKey,
        ]));
    }

    private function rememberShipmentContext(Request $request, array $responseData, array $payload): ?string
    {
        $contextKey = $this->storeShipmentContext($responseData, $payload, []);

        if ($contextKey !== null && $request->hasSession()) {
            $request->session()->put('kay_paolo.last_shipment_key', $contextKey);
        }

        return $contextKey;
    }

    private function storeShipmentContext(array $responseData, array $payload, array $selected = [], ?string $contextKey = null): ?string
    {
        $contextKey = $this->validShipmentContextKey($contextKey) ? (string) $contextKey : (string) Str::uuid();

        try {
            Cache::put($this->shipmentContextCacheKey($contextKey), [
                'response' => $responseData,
                'payload' => $payload,
                'selected' => $selected,
            ], now()->addHours(6));
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }

        return $contextKey;
    }

    private function attachDocumentContextKey(array &$response, ?string $contextKey): void
    {
        if ($contextKey === null) {
            return;
        }

        if (!is_array($response['data'] ?? null)) {
            $response['data'] = [];
        }

        $response['data']['document_context_key'] = $contextKey;
    }

    public function shippingHistory(Request $request): JsonResponse
    {
        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'bocicot/shipping-history-filter'],
            ['endpoint' => 'web-api/shipping-history-filter-bocicot', 'web' => true],
            ['endpoint' => 'kay-paolo/shipping-history-filter'],
        ], $request);
    }

    public function voidShipment(Request $request): JsonResponse
    {
        return $this->forwardAuthenticatedWithFallback([
            ['endpoint' => 'bocicot/void-shipping'],
            ['endpoint' => 'web-api/void-shipping-bocicot', 'web' => true],
            ['endpoint' => 'web-api/void-shipment-bocicot', 'web' => true],
            ['endpoint' => 'kay-paolo/void-shipping'],
        ], $request);
    }

    public function tracking(Request $request): JsonResponse
    {
        $response = $this->postWithFallback([
            ['endpoint' => 'bocicot/validate-tracking'],
            ['endpoint' => 'web-api/validate-tracking-bocicot', 'web' => true],
            ['endpoint' => 'kay-paolo/validate-tracking'],
        ], $request->except('_token'), $request->bearerToken());

        return $this->jsonResponse($response);
    }

    public function emailShipment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'shipment_id' => ['nullable'],
            'shipping_id' => ['nullable'],
            'id' => ['nullable'],
            'invoice' => ['nullable', 'string'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'shipment_number' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'package_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'service_name' => ['nullable', 'string', 'max:180'],
            'created_at' => ['nullable', 'string', 'max:80'],
            'shipper_name' => ['nullable', 'string', 'max:180'],
            'shipper_address' => ['nullable', 'string', 'max:500'],
            'shipper_contact' => ['nullable', 'string', 'max:180'],
            'consignee_name' => ['nullable', 'string', 'max:180'],
            'consignee_address' => ['nullable', 'string', 'max:500'],
            'consignee_contact' => ['nullable', 'string', 'max:180'],
            'label_url' => ['nullable', 'string', 'max:1000'],
            'receipt_url' => ['nullable', 'string', 'max:1000'],
        ]);

        $token = $request->bearerToken() ?: session('zion.access_token');
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please login to Kay Paolo first.',
            ], 401);
        }

        $shipmentNumber = $validated['shipment_number']
            ?? $validated['tracking_number']
            ?? $validated['invoice']
            ?? $validated['id']
            ?? $validated['shipment_id']
            ?? $validated['shipping_id']
            ?? 'Pending';

        $query = array_filter([
            'shipment_id' => $validated['shipment_id'] ?? $validated['shipping_id'] ?? null,
            'invoice' => $validated['invoice'] ?? null,
            'id' => $validated['tracking_number'] ?? $validated['shipment_number'] ?? $validated['id'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        $mailer = $this->shipmentConfirmationMailerName();
        if ($mailer === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shipment confirmation email is not configured for delivery.',
            ], 502);
        }

        $this->primeShipmentDocuments($query, [
            'response' => [
                'shipment_id' => $validated['shipment_id'] ?? $validated['shipping_id'] ?? null,
                'invoice_num' => $validated['invoice'] ?? null,
                'tracking_number' => $validated['tracking_number'] ?? $validated['shipment_number'] ?? null,
                'created_at' => $validated['created_at'] ?? null,
                'selected_shipper' => $validated['service_name'] ?? null,
                'package_count' => $validated['package_count'] ?? null,
            ],
            'payload' => [
                'from_name' => $validated['shipper_name'] ?? null,
                'from_address' => $validated['shipper_address'] ?? null,
                'from_phone' => $validated['shipper_contact'] ?? null,
                'from_email' => $validated['shipper_contact'] ?? null,
                'to_name' => $validated['consignee_name'] ?? null,
                'consignee_name' => $validated['consignee_name'] ?? null,
                'to_address' => $validated['consignee_address'] ?? null,
                'to_phone_1' => $validated['consignee_contact'] ?? null,
                'consignee_phone' => $validated['consignee_contact'] ?? null,
                'delivery_option' => $validated['service_name'] ?? null,
                'package_count' => $validated['package_count'] ?? null,
            ],
            'selected' => [],
        ]);

        try {
            Mail::mailer($mailer)->to($validated['email'])->send(new ConfirmShipmentMail([
                'recipientName' => $validated['recipient_name'] ?? null,
                'shipmentNumber' => (string) $shipmentNumber,
                'trackingNumber' => (string) ($validated['tracking_number'] ?? $shipmentNumber),
                'packageCount' => (int) ($validated['package_count'] ?? 1),
                'serviceName' => $validated['service_name'] ?? 'Shipping Service',
                'createdAt' => $validated['created_at'] ?? now()->format('M d, Y'),
                'shipperName' => $validated['shipper_name'] ?? 'Kay Paolo Shipping',
                'shipperAddress' => $validated['shipper_address'] ?? '414 Main St, Asbury Park, NJ 07712',
                'shipperContact' => $validated['shipper_contact'] ?? 'info@kaypaoloshipping.com',
                'consigneeName' => $validated['consignee_name'] ?? 'Destination Customer',
                'consigneeAddress' => $validated['consignee_address'] ?? 'Destination address pending',
                'consigneeContact' => $validated['consignee_contact'] ?? 'Phone pending',
                'labelUrl' => $validated['label_url'] ?? route('shipment.label', $query),
                'receiptUrl' => $validated['receipt_url'] ?? route('shipment.receipt', $query),
                'trackingUrl' => route('tracking'),
                'confirmationUrl' => route('shipment.confirmation'),
                'homeUrl' => route('home'),
            ]));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to send shipment confirmation email.',
                'error' => config('app.debug') ? $exception->getMessage() : null,
            ], 502);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment confirmation email sent.',
            'email' => $validated['email'],
            'shipment_number' => $shipmentNumber,
            'mailer' => $mailer,
        ]);
    }

    public function shipmentLabel(Request $request): BinaryFileResponse|RedirectResponse|JsonResponse|Response
    {
        return $this->serveGeneratedPdf($request, 'label');
    }

    public function shipmentReceipt(Request $request): BinaryFileResponse|RedirectResponse|JsonResponse|Response
    {
        return $this->serveGeneratedPdf($request, 'receipt');
    }

    public function storedLabel(string $filename): BinaryFileResponse|JsonResponse
    {
        return $this->serveStoredPdf('label', $filename);
    }

    public function storedReceipt(string $filename): BinaryFileResponse|JsonResponse
    {
        return $this->serveStoredPdf('receipts', $filename);
    }

    private function serveGeneratedPdf(Request $request, string $type): BinaryFileResponse|RedirectResponse|JsonResponse|Response
    {
        $query = $request->query();
        $invoice = $this->documents->resolveInvoice($query);
        $existingPath = $type === 'label'
            ? ($invoice !== '' ? $this->documents->labelPath($invoice) : '')
            : ($invoice !== '' ? $this->documents->receiptPath($invoice) : '');

        try {
            $shipment = $this->resolveShipmentContext($request, $query);
            $hasDocumentDetails = $this->documents->shipmentHasPackageDetails($shipment)
                || $this->documents->shipmentHasDocumentDetails($shipment);

            if (!$hasDocumentDetails && $existingPath !== '' && is_file($existingPath) && filesize($existingPath) > 4) {
                $path = $existingPath;
            } else {
                if ($hasDocumentDetails) {
                    $query['regen'] = '1';
                }

                $path = $type === 'label'
                    ? $this->documents->ensureLabelPdf($query, $shipment)
                    : $this->documents->ensureReceiptPdf($query, $shipment);
            }
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to generate PDF document.',
            ], 500);
        }

        if ($request->boolean('download')) {
            return response()->download($path, basename($path), [
                'Content-Type' => 'application/pdf',
            ]);
        }

        if ($invoice !== '') {
            $url = $type === 'label'
                ? $this->documents->labelPublicUrl($invoice)
                : $this->documents->receiptPublicUrl($invoice);

            return redirect()->to($url);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    private function serveStoredPdf(string $directory, string $filename): BinaryFileResponse|JsonResponse
    {
        $safeName = basename($filename);
        if (!preg_match('/^[A-Za-z0-9_-]+\.pdf$/', $safeName)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid document filename.',
            ], 404);
        }

        $prefix = $directory === 'label' ? 'label' : 'receipt';
        $invoice = $this->documents->invoiceFromFilename($safeName, $prefix);
        $path = storage_path('app/public/'.$directory.'/'.$safeName);
        if ($invoice !== '') {
            try {
                $query = ['invoice' => $invoice];
                $shipment = $this->resolveShipmentContext(request(), $query);
                $hasDocumentDetails = $this->documents->shipmentHasPackageDetails($shipment)
                    || $this->documents->shipmentHasDocumentDetails($shipment);

                if ($hasDocumentDetails) {
                    $query['regen'] = '1';
                }
                if ($hasDocumentDetails || !is_file($path)) {
                    if ($directory === 'label') {
                        $this->documents->ensureLabelPdf($query, $shipment);
                    } else {
                        $this->documents->ensureReceiptPdf($query, $shipment);
                    }
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if (!is_file($path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'PDF document not found.',
            ], 404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    private function resolveShipmentContext(Request $request, array $query): array
    {
        $cachedShipment = $this->shipmentContextFromCache(
            $query['context_key'] ?? $query['document_context_key'] ?? null
        );

        if ($cachedShipment === [] && $request->hasSession()) {
            $cachedShipment = $this->shipmentContextFromCache(session('kay_paolo.last_shipment_key'));
        }

        $sessionShipment = $cachedShipment !== []
            ? $cachedShipment
            : (array) session('kay_paolo.last_shipment', []);
        $remote = $this->fetchShipmentRecord($request, $query);

        if (!$remote) {
            return $sessionShipment;
        }

        $sessionPayload = is_array($sessionShipment['payload'] ?? null) ? $sessionShipment['payload'] : [];
        $sessionSelected = is_array($sessionShipment['selected'] ?? null) ? $sessionShipment['selected'] : [];
        $remotePayload = array_filter($remote, static fn ($value) => $value !== null && $value !== '' && $value !== []);

        $remotePackages = $remote['packages'] ?? $remote['package'] ?? null;
        if (is_string($remotePackages)) {
            $decodedPackages = json_decode($remotePackages, true);
            $remotePackages = is_array($decodedPackages) ? $decodedPackages : null;
        }
        $remoteDimensions = $remote['dimensions'] ?? null;
        if (is_string($remoteDimensions)) {
            $decodedDimensions = json_decode($remoteDimensions, true);
            $remoteDimensions = is_array($decodedDimensions) ? $decodedDimensions : null;
        }

        return [
            'response' => $remote,
            'payload' => array_merge($sessionPayload, $remotePayload, array_filter([
                'package_description' => $remote['package_description'] ?? $remote['description'] ?? null,
                'package_count' => $remote['package_count'] ?? null,
                'dimensions' => is_array($remoteDimensions) && $remoteDimensions !== [] ? $remoteDimensions : null,
                'packages' => is_array($remotePackages) && $remotePackages !== [] ? $remotePackages : null,
                'delivery_option' => $remote['delivery_option'] ?? $remote['selected_shipper'] ?? null,
                'selected_shipper' => $remote['selected_shipper'] ?? null,
                'delivery_location' => $remote['delivery_location'] ?? null,
                'deliveryEstimateDate' => $remote['deliveryEstimateDate']
                    ?? $remote['deliveryDate']
                    ?? $remote['delivery_estimate_date']
                    ?? $remote['delivery_date']
                    ?? $remote['expected_delivery_date']
                    ?? $remote['expected_arrival_date']
                    ?? $remote['estimated_delivery_date']
                    ?? $remote['estimated_arrival_date']
                    ?? $remote['eta']
                    ?? $remote['arrives_on']
                    ?? $remote['delivered_by']
                    ?? $remote['delivery_time']
                    ?? $remote['commitment']
                    ?? $remote['transit_time']
                    ?? null,
                'account_number' => $remote['account_number']
                    ?? $remote['accountNumber']
                    ?? $remote['account_no']
                    ?? $remote['accountNo']
                    ?? $remote['customer_account']
                    ?? $remote['customerAccount']
                    ?? $remote['customer_account_number']
                    ?? $remote['customerAccountNumber']
                    ?? $remote['from_account']
                    ?? $remote['fromAccount']
                    ?? $remote['phone_or_account']
                    ?? $remote['phoneOrAccount']
                    ?? $remote['customer_id']
                    ?? $remote['customerId']
                    ?? $remote['quote_user_id']
                    ?? $remote['quoteUserId']
                    ?? null,
                'from_name' => $remote['from_name'] ?? $remote['shipper_name'] ?? null,
                'from_email' => $remote['from_email'] ?? $remote['shipper_email'] ?? $remote['customer_email'] ?? $remote['email'] ?? null,
                'from_phone' => $remote['from_phone'] ?? $remote['shipper_phone'] ?? $remote['shipper_contact'] ?? $remote['shipper_mobile'] ?? $remote['phone'] ?? null,
                'from_address' => $remote['shipper_address'] ?? $remote['from_address'] ?? null,
                'from_city' => $remote['shipper_city'] ?? $remote['shipper_address_city'] ?? $remote['from_city'] ?? null,
                'from_state' => $remote['shipper_state'] ?? $remote['shipper_address_state'] ?? $remote['from_state'] ?? null,
                'from_zip' => $remote['shipper_zip'] ?? $remote['shipper_address_zip'] ?? $remote['from_zip'] ?? null,
                'from_country_name' => $remote['shipper_country'] ?? $remote['from_country_name'] ?? null,
                'to_name' => $remote['to_name'] ?? $remote['consignee_name'] ?? null,
                'to_phone_1' => $remote['to_phone_1'] ?? $remote['to_phone'] ?? $remote['consignee_phone'] ?? $remote['consignee_contact'] ?? $remote['receiver_phone'] ?? $remote['recipient_phone'] ?? null,
                'to_phone_2' => $remote['to_phone_2'] ?? $remote['consignee_homephone'] ?? null,
                'consignee_phone' => $remote['consignee_phone'] ?? $remote['consignee_contact'] ?? $remote['to_phone_1'] ?? $remote['to_phone'] ?? null,
                'to_address' => $remote['consignee_address'] ?? $remote['to_address'] ?? null,
                'to_city' => $remote['consignee_city'] ?? $remote['consignee_address_city'] ?? $remote['to_city'] ?? null,
                'to_state' => $remote['consignee_state'] ?? $remote['consignee_address_state'] ?? $remote['to_state'] ?? null,
                'to_zip' => $remote['consignee_zip'] ?? $remote['consignee_address_zip'] ?? $remote['to_zip'] ?? null,
                'to_country_name' => $remote['consignee_country'] ?? $remote['to_country_name'] ?? null,
                'payment_type' => $remote['payment_type'] ?? null,
                'total_value' => $remote['total_value'] ?? $remote['package_value'] ?? null,
            ], static fn ($value) => $value !== null && $value !== '' && $value !== [])),
            'selected' => array_merge($sessionSelected, array_filter([
                'freight' => $remote['freight'] ?? null,
                'tax' => $remote['tax'] ?? null,
                'total' => $remote['grand_total'] ?? $remote['final_total'] ?? $remote['total'] ?? null,
                'insurance' => $remote['insurance'] ?? null,
                'home_delivery' => $remote['home_delivery'] ?? $remote['home_delivery_fee'] ?? $remote['delivery_fee'] ?? $remote['delivery'] ?? null,
                'eta' => $remote['deliveryEstimateDate'] ?? $remote['delivery_estimate_date'] ?? $remote['delivery_date'] ?? $remote['expected_arrival_date'] ?? $remote['estimated_delivery_date'] ?? $remote['eta'] ?? $remote['arrives_on'] ?? $remote['delivered_by'] ?? $remote['delivery_time'] ?? $remote['commitment'] ?? $remote['transit_time'] ?? null,
                'service' => $remote['selected_shipper'] ?? $remote['delivery_option'] ?? null,
            ], static fn ($value) => $value !== null && $value !== '')),
        ];
    }

    private function shipmentContextFromCache(mixed $contextKey): array
    {
        if (!$this->validShipmentContextKey($contextKey)) {
            return [];
        }

        try {
            $shipment = Cache::get($this->shipmentContextCacheKey((string) $contextKey), []);
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }

        return is_array($shipment) ? $shipment : [];
    }

    private function validShipmentContextKey(mixed $contextKey): bool
    {
        return is_string($contextKey)
            && preg_match('/^[A-Za-z0-9_-]{16,80}$/', $contextKey) === 1;
    }

    private function shipmentContextCacheKey(string $contextKey): string
    {
        return 'kay_paolo:shipment_context:'.$contextKey;
    }

    private function fetchShipmentRecord(Request $request, array $query): ?array
    {
        $token = $request->bearerToken()
            ?: session('zion.access_token')
            ?: $request->query('access_token')
            ?: $request->query('token');

        if (!$token) {
            return null;
        }

        $invoice = $this->documents->resolveInvoice($query);
        $shipmentId = $query['shipment_id'] ?? $query['shipping_id'] ?? null;
        $search = trim((string) ($invoice !== '' ? $invoice : ($query['id'] ?? '')));

        if ($search === '' && !$shipmentId) {
            return null;
        }

        $response = $this->postWithFallback([
            ['endpoint' => 'bocicot/shipping-history-filter'],
            ['endpoint' => 'web-api/shipping-history-filter-bocicot', 'web' => true],
            ['endpoint' => 'kay-paolo/shipping-history-filter'],
        ], array_filter([
            'search' => $search !== '' ? $search : null,
            'date_range' => '365 Days',
            'limit' => 50,
            'shipment_id' => $shipmentId,
            'shipping_id' => $shipmentId,
        ]), $token);

        if (!($response['ok'] ?? false)) {
            return null;
        }

        $rows = $response['data']['shippings']
            ?? $response['data']['shipping_history']
            ?? $response['data']['data']
            ?? [];

        if (!is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rowId = (string) ($row['id'] ?? $row['shipment_id'] ?? $row['shipping_id'] ?? '');
            $rowInvoice = (string) ($row['invoice_num'] ?? $row['invoice'] ?? '');
            if ($shipmentId && $rowId === (string) $shipmentId) {
                return $row;
            }
            if ($invoice !== '' && $rowInvoice === $invoice) {
                return $row;
            }
        }

        $first = $rows[0] ?? null;

        return is_array($first) ? $first : null;
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

        return $this->jsonResponse($this->postWithFallback(
            $targets,
            $payload ?? $request->except('_token'),
            $token
        ));
    }

    private function postWithFallback(array $targets, array $payload, ?string $token = null): array
    {
        $lastResponse = null;

        foreach ($targets as $target) {
            $endpoint = $target['endpoint'];
            $lastResponse = !empty($target['web'])
                ? $this->zion->postWeb($endpoint, $payload, $token)
                : $this->zion->post($endpoint, $payload, $token);

            if ($lastResponse['ok'] && !$this->shouldTryFallback($lastResponse)) {
                return $lastResponse;
            }

            if (!$this->shouldTryFallback($lastResponse)) {
                return $lastResponse;
            }
        }

        return $lastResponse ?? [
            'ok' => false,
            'status' => 502,
            'data' => [
                'status' => 'error',
                'message' => 'Unable to reach the shipping API.',
            ],
        ];
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
            'agent_id' => $payload['agent_id'] ?? null,
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
            'package_count' => $this->dimensionPieceCount($dimensions),
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
            'delivery_type' => $deliveryLocation,
            'deliveryType' => $deliveryLocation,
            'is_home_delivery' => $this->isHomeDelivery($deliveryLocation) ? 1 : 0,
            'home_delivery_required' => $this->isHomeDelivery($deliveryLocation) ? 1 : 0,
            'delivery_description' => $payload['delivery_description'] ?? '',
            'payment_type' => $payload['payment_type'] ?? 'PAID AT AGENT',
            'deliveryEstimatePrice' => $payload['deliveryEstimatePrice'] ?? null,
            'deliveryEstimateDate' => $payload['deliveryEstimateDate'] ?? null,
            'promo' => $payload['promo'] ?? $payload['coupon_code'] ?? $payload['coupon'] ?? $payload['promo_code'] ?? '',
            'coupon_code' => $payload['coupon_code'] ?? $payload['promo'] ?? $payload['coupon'] ?? $payload['promo_code'] ?? '',
            'coupon' => $payload['coupon'] ?? $payload['coupon_code'] ?? $payload['promo'] ?? '',
            'promo_code' => $payload['promo_code'] ?? $payload['promo'] ?? $payload['coupon_code'] ?? '',
            'discount_code' => $payload['discount_code'] ?? $payload['coupon_code'] ?? $payload['promo'] ?? '',
            'extra_service_charge' => $payload['extra_service_charge'] ?? '',
            'include_in_receipt' => $payload['include_in_receipt'] ?? $payload['include_receipt'] ?? 0,
            'flaterateinside' => $this->hasFlatRate($flatRate, $shipmentType) ? 1 : 0,
            'flat_rate_price' => $this->padArray($payload['flat_rate_price'] ?? [], $rowCount, ''),
            'flat_rate_label' => $this->padArray($payload['flat_rate_label'] ?? [], $rowCount, ''),
            'fragile_shipment' => $fragileShipment,
            'is_fragile_shipment' => $fragileShipment,
        ]);
    }

    private function sanitizeFlatRatePayload(array $payload): array
    {
        $toCountry = $payload['to_country']
            ?? $payload['country']
            ?? $payload['to']['country']
            ?? null;
        $toCountryName = $payload['to_country_name']
            ?? $payload['country_name']
            ?? $payload['to']['country_name']
            ?? null;
        $fromState = $payload['from_state']
            ?? $payload['origin_state']
            ?? $payload['from']['state']
            ?? null;

        return $this->compactPayload([
            'user_id' => $payload['user_id'] ?? $payload['quote_user_id'] ?? null,
            'quote_user_id' => $payload['quote_user_id'] ?? $payload['user_id'] ?? null,
            'agent_id' => $payload['agent_id'] ?? $payload['agentId'] ?? null,
            'to_country' => $toCountry,
            'country' => $toCountry,
            'country_code' => $toCountry,
            'to_country_name' => $toCountryName,
            'country_name' => $toCountryName,
            'from_state' => $fromState,
            'origin_state' => $fromState,
            'selected_shipper' => $payload['selected_shipper'] ?? $payload['delivery_option'] ?? $payload['service'] ?? null,
            'delivery_option' => $payload['delivery_option'] ?? $payload['selected_shipper'] ?? $payload['service'] ?? null,
        ]);
    }

    private function sanitizeQuotePayload(array $payload): array
    {
        $dimensions = $this->normalizeShipmentDimensions($payload);
        $rowCount = $this->dimensionRowCount($dimensions);
        $flatRate = $this->padArray($payload['flat_rate'] ?? [], $rowCount, '0');
        $shipmentType = $this->padArray($payload['shipment_type'] ?? [], $rowCount, '');
        $deliveryLocation = $this->normalizeDeliveryLocation($payload['delivery_location'] ?? $payload['deliveryLocation'] ?? '');
        $declaredValue = $this->positiveNumber($payload['total_value'] ?? $payload['package_value'] ?? null, 10);
        $fragileShipment = $payload['is_fragile_shipment'] ?? $payload['fragile_shipment'] ?? 0;
        $promo = trim((string) ($payload['promo'] ?? $payload['coupon_code'] ?? $payload['coupon'] ?? $payload['promo_code'] ?? $payload['discount_code'] ?? ''));

        return $this->compactPayload([
            'user_id' => $payload['user_id'] ?? $payload['quote_user_id'] ?? null,
            'quote_user_id' => $payload['quote_user_id'] ?? $payload['user_id'] ?? null,
            'agent_id' => $payload['agent_id'] ?? null,
            'agentId' => $payload['agentId'] ?? $payload['agent_id'] ?? null,
            'created_by' => $payload['created_by'] ?? $payload['agent_id'] ?? null,
            'created_by_id' => $payload['created_by_id'] ?? $payload['agent_id'] ?? null,
            'phone_or_account' => $payload['phone_or_account'] ?? null,
            'from_name' => $payload['from_name'] ?? null,
            'from_email' => $payload['from_email'] ?? null,
            'from_phone' => $payload['from_phone'] ?? null,
            'from_account' => $payload['from_account'] ?? null,
            'account_number' => $payload['from_account'] ?? $payload['account_number'] ?? null,
            'from_country_name' => $payload['from_country_name'] ?? null,
            'from_country' => $payload['from_country'] ?? null,
            'from_address' => $payload['from_address'] ?? null,
            'from_apt' => $payload['from_apt'] ?? '',
            'from_zip' => $payload['from_zip'] ?? null,
            'from_city' => $payload['from_city'] ?? null,
            'from_state' => $payload['from_state'] ?? null,
            'to_country_name' => $payload['to_country_name'] ?? null,
            'to_country' => $payload['to_country'] ?? null,
            'to_address' => $payload['to_address'] ?? null,
            'to_apt' => $payload['to_apt'] ?? '',
            'to_zip_input' => $payload['to_zip_input'] ?? $payload['to_zip'] ?? null,
            'to_city_dropdown' => $payload['to_city_dropdown'] ?? $payload['to_city'] ?? null,
            'to_zip' => $payload['to_zip'] ?? null,
            'to_city' => $payload['to_city'] ?? null,
            'to_state' => $payload['to_state'] ?? null,
            'to_name' => $payload['to_name'] ?? $payload['consignee_name'] ?? null,
            'consignee_name' => $payload['consignee_name'] ?? $payload['to_name'] ?? null,
            'consignee_id' => $payload['consignee_id'] ?? $payload['consignees_id'] ?? null,
            'consignees_id' => $payload['consignees_id'] ?? $payload['consignee_id'] ?? null,
            'to_phone_1' => $payload['to_phone_1'] ?? $payload['consignee_phone'] ?? null,
            'to_phone_2' => $payload['to_phone_2'] ?? $payload['consignee_homephone'] ?? '',
            'consignee_phone' => $payload['consignee_phone'] ?? $payload['to_phone_1'] ?? null,
            'package_count' => $this->dimensionPieceCount($dimensions),
            'total_value' => $declaredValue,
            'package_value' => $declaredValue,
            'dimensions' => $dimensions,
            'flat_rate' => $flatRate,
            'shipment_type' => $shipmentType,
            'packages' => $this->expandedPackages($dimensions, $flatRate, $shipmentType),
            'delivery_location' => $deliveryLocation,
            'deliveryLocation' => $deliveryLocation,
            'delivery_type' => $deliveryLocation,
            'deliveryType' => $deliveryLocation,
            'is_home_delivery' => $this->isHomeDelivery($deliveryLocation) ? 1 : 0,
            'home_delivery_required' => $this->isHomeDelivery($deliveryLocation) ? 1 : 0,
            'promo' => $promo,
            'coupon_code' => $promo,
            'coupon' => $promo,
            'promo_code' => $promo,
            'discount_code' => $promo,
            'extra_service_charge' => $payload['extra_service_charge'] ?? '',
            'include_in_receipt' => $payload['include_in_receipt'] ?? $payload['include_receipt'] ?? 0,
            'flaterateinside' => $this->hasFlatRate($flatRate, $shipmentType) ? 1 : 0,
            'flat_rate_price' => $this->padArray($payload['flat_rate_price'] ?? [], $rowCount, ''),
            'flat_rate_label' => $this->padArray($payload['flat_rate_label'] ?? [], $rowCount, ''),
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
            'weight' => array_map(fn ($value) => $this->nonNegativeNumber($value, 1), $this->padArray($weights, $rowCount, 1)),
            'length' => array_map(fn ($value) => $this->nonNegativeNumber($value, 1), $this->padArray($lengths, $rowCount, 1)),
            'width' => array_map(fn ($value) => $this->nonNegativeNumber($value, 1), $this->padArray($widths, $rowCount, 1)),
            'height' => array_map(fn ($value) => $this->nonNegativeNumber($value, 1), $this->padArray($heights, $rowCount, 1)),
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

    private function dimensionPieceCount(array $dimensions): int
    {
        $total = 0;

        foreach ($dimensions['package_count_ind'] ?? [] as $count) {
            $total += (int) $this->positiveNumber($count, 1);
        }

        return max($total, 1);
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

    private function nonNegativeNumber(mixed $value, float|int $fallback): float|int
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return $fallback;
        }

        $number = is_numeric($value)
            ? (float) $value
            : (float) preg_replace('/[^0-9.-]/', '', $raw);

        return $number >= 0 ? $number : $fallback;
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

    private function isHomeDelivery(string $deliveryLocation): bool
    {
        return str_contains(strtolower($deliveryLocation), 'home');
    }

    private function attachShipmentEmailResult(Request $request, array &$response, array $payload): void
    {
        if (!($response['ok'] ?? false)) {
            return;
        }

        $response['data']['confirmation_email'] = $this->sendShipmentConfirmationForPayload(
            $request,
            is_array($response['data'] ?? null) ? $response['data'] : [],
            $payload
        );
    }

    private function sendShipmentConfirmationForPayload(Request $request, array $responseData, array $payload): array
    {
        $email = $this->firstEmail([
            $payload['from_email'] ?? null,
            $payload['shipper_email'] ?? null,
            $payload['sender_email'] ?? null,
            $payload['customer_email'] ?? null,
            $payload['email'] ?? null,
            $payload['email_address'] ?? null,
            $payload['shipper_contact'] ?? null,
            $payload['sender_contact'] ?? null,
            $payload['customer_contact'] ?? null,
            $payload['contact'] ?? null,
            $responseData['shipper_email'] ?? null,
            $responseData['from_email'] ?? null,
            $responseData['sender_email'] ?? null,
            $responseData['customer_email'] ?? null,
            $responseData['email'] ?? null,
            $responseData['email_address'] ?? null,
            $responseData['shipper_contact'] ?? null,
            $responseData['sender_contact'] ?? null,
            $responseData['customer_contact'] ?? null,
            $responseData['contact'] ?? null,
            $responseData['shipper'] ?? null,
            $responseData['sender'] ?? null,
            $responseData['customer'] ?? null,
            $responseData['user'] ?? null,
            $responseData['shipping'] ?? null,
            $responseData['shipping_data'] ?? null,
            $responseData['data'] ?? null,
            session('zion.user.email'),
            session('zion.user'),
        ]);

        if (!$email) {
            return [
                'status' => 'skipped',
                'message' => 'No customer email was available for confirmation.',
            ];
        }

        $shipmentNumber = (string) ($responseData['tracking_number']
            ?? $responseData['tracking_numbers']
            ?? $responseData['invoice_num']
            ?? $responseData['awb']
            ?? $payload['tracking_number']
            ?? $payload['quote_id']
            ?? 'Pending');
        $invoice = (string) ($responseData['invoice_num'] ?? $responseData['invoice'] ?? $payload['invoice_num'] ?? '');
        $shipmentId = (string) ($responseData['shipment_id'] ?? $responseData['shipping_id'] ?? $responseData['id'] ?? '');
        $query = array_filter([
            'shipment_id' => $shipmentId ?: null,
            'invoice' => $invoice ?: null,
            'id' => $shipmentNumber !== 'Pending' ? $shipmentNumber : null,
        ], static fn ($value) => $value !== null && $value !== '');

        $this->primeShipmentDocuments($query, [
            'response' => $responseData,
            'payload' => $payload,
            'selected' => [],
        ]);

        $mailer = $this->shipmentConfirmationMailerName();
        if ($mailer === null) {
            return [
                'status' => 'error',
                'email' => $email,
                'message' => 'Shipment confirmation email is not configured for delivery.',
            ];
        }

        try {
            $shipperEmail = $this->firstEmail([
                $payload['from_email'] ?? null,
                $payload['shipper_email'] ?? null,
                $payload['sender_email'] ?? null,
                $payload['customer_email'] ?? null,
                $payload['email'] ?? null,
                $payload['email_address'] ?? null,
                $payload['shipper_contact'] ?? null,
                $payload['sender_contact'] ?? null,
                $payload['customer_contact'] ?? null,
                $payload['contact'] ?? null,
                $email,
            ]);

            Mail::mailer($mailer)->to($email)->send(new ConfirmShipmentMail([
                'recipientName' => $payload['from_name'] ?? session('zion.user.name') ?? null,
                'shipmentNumber' => $shipmentNumber,
                'trackingNumber' => $shipmentNumber,
                'packageCount' => (int) ($payload['package_count'] ?? 1),
                'serviceName' => $payload['delivery_option'] ?? $payload['selected_shipper'] ?? 'Shipping Service',
                'createdAt' => now()->format('M d, Y'),
                'shipperName' => $payload['from_name'] ?? 'Kay Paolo Shipping',
                'shipperAddress' => $this->addressText($payload, 'from'),
                'shipperContact' => $this->contactText([
                    $payload['from_phone'] ?? null,
                    $shipperEmail,
                ]),
                'consigneeName' => $payload['to_name'] ?? $payload['consignee_name'] ?? 'Destination Customer',
                'consigneeAddress' => $this->addressText($payload, 'to'),
                'consigneeContact' => $this->contactText([
                    $payload['to_phone_1'] ?? null,
                    $payload['consignee_phone'] ?? null,
                    $payload['to_phone_2'] ?? null,
                ]),
                'labelUrl' => route('shipment.label', $query),
                'receiptUrl' => route('shipment.receipt', $query),
                'trackingUrl' => route('tracking'),
                'confirmationUrl' => route('shipment.confirmation'),
                'homeUrl' => route('home'),
            ]));
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'status' => 'error',
                'email' => $email,
                'message' => 'Unable to send shipment confirmation email.',
                'error' => config('app.debug') ? $exception->getMessage() : null,
            ];
        }

        return [
            'status' => 'success',
            'email' => $email,
            'mailer' => $mailer,
        ];
    }

    private function shipmentConfirmationMailerName(): ?string
    {
        $zeptoToken = trim((string) config('services.zeptomail.token'));
        if ($zeptoToken !== '') {
            return 'zeptomail';
        }

        $defaultMailer = trim((string) config('mail.default', 'log'));
        $normalizedMailer = strtolower($defaultMailer);

        if ($defaultMailer === '' || in_array($normalizedMailer, ['log', 'array', 'zeptomail'], true)) {
            return null;
        }

        return $defaultMailer;
    }

    private function primeShipmentDocuments(array $query, array $shipment): void
    {
        if ($this->documents->resolveInvoice($query) === '' && empty($query['id'])) {
            return;
        }

        try {
            if ($this->documents->shipmentHasPackageDetails($shipment) || $this->documents->shipmentHasDocumentDetails($shipment)) {
                $query['regen'] = '1';
            }

            $this->documents->ensureLabelPdf($query, $shipment);
            $this->documents->ensureReceiptPdf($query, $shipment);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function firstEmail(array $values): ?string
    {
        foreach ($values as $value) {
            $email = $this->emailFromValue($value);
            if ($email !== null) {
                return $email;
            }
        }

        return null;
    }

    private function emailFromValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            foreach ($value as $nestedValue) {
                $email = $this->emailFromValue($nestedValue);
                if ($email !== null) {
                    return $email;
                }
            }

            return null;
        }

        if (is_object($value)) {
            return $this->emailFromValue((array) $value);
        }

        $candidate = trim((string) $value);
        if ($candidate === '') {
            return null;
        }

        if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            return $candidate;
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $candidate, $matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    private function addressText(array $payload, string $side): string
    {
        $prefix = $side === 'from' ? 'from' : 'to';

        return trim(implode("\n", array_filter([
            trim((string) ($payload[$prefix.'_address'] ?? '').' '.(string) ($payload[$prefix.'_apt'] ?? '')),
            trim(implode(' ', array_filter([
                $payload[$prefix.'_city'] ?? null,
                $payload[$prefix.'_state'] ?? null,
                $payload[$prefix.'_zip'] ?? null,
            ]))),
            $payload[$prefix.'_country_name'] ?? $payload[$prefix.'_country'] ?? null,
        ]))) ?: ($side === 'from' ? '414 Main St, Asbury Park, NJ 07712' : 'Destination address pending');
    }

    private function contactText(array $values): string
    {
        return implode(' / ', array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $values
        )))) ?: 'Phone pending';
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
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $message = strtolower((string) ($data['message'] ?? ''));
        $error = strtolower((string) ($data['error'] ?? ''));
        $appLocked = strtolower((string) ($data['app_locked'] ?? ''));

        return $status === 0
            || in_array($status, [404, 405], true)
            || $status >= 500
            || str_contains($message, 'session store not set')
            || str_contains($message, 'app is locked')
            || ($error === 'true' && $appLocked === 'true')
            || isset($data['html']);
    }

    private function normalizeCountries(array $payload): array
    {
        $rows = $payload['countries']
            ?? $payload['data']['countries']
            ?? $payload['data']['data']
            ?? $payload['data']
            ?? [];

        if (!is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($country, $key) {
                if (is_string($country)) {
                    $code = strtoupper(trim((string) $key));
                    $name = trim($country);

                    if ($code === '' || $name === '') {
                        return null;
                    }

                    return [
                        'id' => null,
                        'code' => $code,
                        'name' => $name,
                        'dial_code' => null,
                        'zip_code_supported' => null,
                        'flat_rate_supported' => null,
                    ];
                }

                if (!is_array($country)) {
                    return null;
                }

                $code = strtoupper(trim((string) ($country['alpha_2_code'] ?? $country['code'] ?? $country['value'] ?? (is_string($key) ? $key : ''))));
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
            ?? $payload['data']['payment_options']
            ?? $payload['data']
            ?? [];

        if (!is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($option, $key) {
                if (is_string($option)) {
                    return [
                        'value' => is_string($key) ? $key : $option,
                        'label' => is_string($key) ? $option : $this->paymentLabel($option),
                    ];
                }

                if (!is_array($option)) {
                    return null;
                }

                $value = trim((string) ($option['value'] ?? $option['code'] ?? $option['type'] ?? $option['name'] ?? (is_string($key) ? $key : '')));
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
