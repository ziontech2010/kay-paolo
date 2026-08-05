<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Picqer\Barcode\BarcodeGeneratorPNG;

class ShipmentDocumentPdfService
{
    public function labelPath(string $invoice): string
    {
        return storage_path('app/public/label/label_'.$this->safe($invoice).'.pdf');
    }

    public function receiptPath(string $invoice): string
    {
        return storage_path('app/public/receipts/receipt_'.$this->safe($invoice).'.pdf');
    }

    public function labelPublicUrl(string $invoice): string
    {
        return url('label/label_'.$this->safe($invoice).'.pdf');
    }

    public function receiptPublicUrl(string $invoice): string
    {
        return url('receipts/receipt_'.$this->safe($invoice).'.pdf');
    }

    public function hasLabel(string $invoice): bool
    {
        $path = $this->labelPath($invoice);

        return is_file($path) && filesize($path) > 4;
    }

    public function hasReceipt(string $invoice): bool
    {
        $path = $this->receiptPath($invoice);

        return is_file($path) && filesize($path) > 4;
    }

    public function resolveInvoice(array $query): string
    {
        foreach (['invoice', 'invoice_num'] as $key) {
            $value = trim((string) ($query[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $tracking = trim((string) ($query['id'] ?? $query['tracking'] ?? $query['tracking_number'] ?? ''));
        if ($tracking !== '' && preg_match('/(\d{4,})/', $tracking, $matches)) {
            return $matches[1];
        }

        return $tracking;
    }

    public function labelNumbers(array $query): array
    {
        $raw = (string) ($query['id'] ?? $query['tracking'] ?? $query['tracking_number'] ?? '');
        $labels = collect(explode(',', $raw))
            ->map(fn ($label) => trim((string) $label))
            ->filter()
            ->values()
            ->all();

        return $labels ?: ['Pending'];
    }

    public function ensureLabelPdf(array $query, array $shipment = []): string
    {
        $invoice = $this->resolveInvoice($query) ?: 'pending';
        $path = $this->labelPath($invoice);

        if (is_file($path) && filesize($path) > 4 && empty($query['regen'])) {
            return $path;
        }

        $payload = $this->documentPayload($query, $shipment);
        $pdf = Pdf::loadView('documents.pdf.label', $payload)->setPaper('a4', 'portrait');
        $this->write($path, $pdf->output());

        return $path;
    }

    public function ensureReceiptPdf(array $query, array $shipment = []): string
    {
        $invoice = $this->resolveInvoice($query) ?: 'pending';
        $path = $this->receiptPath($invoice);

        if (is_file($path) && filesize($path) > 4 && empty($query['regen'])) {
            return $path;
        }

        $payload = $this->documentPayload($query, $shipment);
        $pdf = Pdf::loadView('documents.pdf.receipt', $payload)->setPaper('a4', 'portrait');
        $this->write($path, $pdf->output());

        return $path;
    }

    public function barcodeDataUri(string $value, int $height = 50): string
    {
        $value = trim($value);
        if ($value === '' || strcasecmp($value, 'Pending') === 0) {
            return '';
        }

        if (!extension_loaded('gd')) {
            return '';
        }

        try {
            $generator = new BarcodeGeneratorPNG();
            $png = $generator->getBarcode($value, $generator::TYPE_CODE_128, 2, $height);

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable) {
            return '';
        }
    }

    private function documentPayload(array $query, array $shipment): array
    {
        $response = $shipment['response'] ?? $shipment;
        $payload = $shipment['payload'] ?? [];
        $selected = $shipment['selected'] ?? [];
        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
        $shipping = $response['shipping_data']
            ?? $response['shipping']
            ?? $responseData['shipping_data']
            ?? $responseData['shipping']
            ?? [];

        $labels = $this->labelNumbers($query);
        $invoice = $this->resolveInvoice($query);
        $trackingDisplay = $this->formatShipmentNumber(implode(',', $labels));
        $barcodeValue = $invoice !== '' ? $invoice : preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($labels[0] ?? 'Pending'));
        $deliveryNumber = $invoice !== ''
            ? 'DLV'.substr(preg_replace('/\D/', '', $invoice) ?: $invoice, -6)
            : 'Pending';

        $paymentType = (string) ($payload['payment_type'] ?? $shipping['payment_type'] ?? 'PAID AT AGENT');
        $chargeStatus = str_contains(strtoupper($paymentType), 'PAID') ? 'PAID' : 'DUE';
        $toCity = (string) ($payload['to_city'] ?? $shipping['consignee_city'] ?? $shipping['to_city'] ?? 'Destination');
        $destination = $toCity !== '' ? strtoupper(substr($toCity, 0, 1)).' - '.$toCity : 'Destination';

        $shipperName = (string) ($payload['from_name'] ?? $shipping['shipper_name'] ?? 'Kay Paolo Shipping');
        $shipperAddress = trim(implode("\n", array_filter([
            trim(($payload['from_address'] ?? '').' '.($payload['from_apt'] ?? '')),
            trim(implode(' ', array_filter([
                $payload['from_city'] ?? null,
                $payload['from_state'] ?? null,
                $payload['from_zip'] ?? null,
            ]))),
            $payload['from_country_name'] ?? $payload['from_country'] ?? $shipping['shipper_country'] ?? null,
        ]))) ?: '414 Main St, Asbury Park, NJ 07712';
        $shipperPhone = (string) ($payload['from_phone'] ?? $shipping['shipper_phone'] ?? 'info@kaypaoloshipping.com');

        $consigneeName = (string) ($payload['to_name'] ?? $payload['consignee_name'] ?? $shipping['consignee_name'] ?? 'Destination Customer');
        $consigneeAddress = trim(implode("\n", array_filter([
            trim(($payload['to_address'] ?? '').' '.($payload['to_apt'] ?? '')),
            trim(implode(' ', array_filter([
                $payload['to_city'] ?? null,
                $payload['to_state'] ?? null,
                $payload['to_zip'] ?? null,
            ]))),
            $payload['to_country_name'] ?? $payload['to_country'] ?? $shipping['consignee_country'] ?? null,
        ]))) ?: 'Destination address pending';
        $consigneePhone = (string) ($payload['to_phone_1'] ?? $payload['consignee_phone'] ?? $shipping['consignee_phone'] ?? 'Phone pending');

        $description = (string) ($payload['package_description'] ?? $shipping['package_description'] ?? 'Package');
        $dimensions = $payload['dimensions'] ?? [];
        $weights = array_values((array) ($dimensions['weight'] ?? [1]));
        $lengths = array_values((array) ($dimensions['length'] ?? [1]));
        $widths = array_values((array) ($dimensions['width'] ?? [1]));
        $heights = array_values((array) ($dimensions['height'] ?? [1]));
        $counts = array_values((array) ($dimensions['package_count_ind'] ?? [1]));
        $packageCount = max(1, (int) array_sum(array_map('intval', $counts)) ?: count($labels));
        $totalWeight = array_sum(array_map('floatval', $weights)) ?: 1;
        $weightDim = number_format((float) ($weights[0] ?? $totalWeight), 0).' lbs, DIM: ('
            .($lengths[0] ?? 1).' X '.($widths[0] ?? 1).' X '.($heights[0] ?? 1).')';

        $items = [];
        $rowCount = max(count($weights), count($lengths), count($widths), count($heights), count($counts), 1);
        for ($i = 0; $i < $rowCount; $i++) {
            $l = (float) ($lengths[$i] ?? 1);
            $w = (float) ($widths[$i] ?? 1);
            $h = (float) ($heights[$i] ?? 1);
            $items[] = [
                'pieces' => str_pad((string) ((int) ($counts[$i] ?? 1)), 2, '0', STR_PAD_LEFT),
                'description' => $description,
                'weight' => (float) ($weights[$i] ?? 1),
                'volume' => number_format(($l * $w * $h) / 1728, 2, '.', ''),
                'dimensions' => $l.' x '.$w.' x '.$h,
            ];
        }

        $freight = (float) ($selected['freight'] ?? $response['freight'] ?? $shipping['freight'] ?? $selected['total'] ?? $selected['price'] ?? $payload['deliveryEstimatePrice'] ?? 0);
        $tax = (float) ($selected['tax'] ?? $response['tax'] ?? $shipping['tax'] ?? 0);
        $total = (float) ($selected['total'] ?? $selected['price'] ?? $payload['deliveryEstimatePrice'] ?? $response['total'] ?? $shipping['total'] ?? ($freight + $tax));
        $declared = (float) ($payload['total_value'] ?? $payload['package_value'] ?? 0);
        $deliveryOption = (string) ($payload['delivery_option'] ?? $payload['selected_shipper'] ?? $selected['service'] ?? 'Shipping Service');
        $deliveryLocation = (string) ($payload['delivery_location'] ?? '');
        $serviceSummary = trim($deliveryOption.($deliveryLocation !== '' ? ' | '.$deliveryLocation : ''));
        $created = $response['created_at'] ?? $shipping['created_at'] ?? now()->toDateTimeString();
        $deliveryDate = $payload['deliveryEstimateDate'] ?? $selected['eta'] ?? $response['delivery_date'] ?? 'Pending';

        return [
            'invoice' => $invoice !== '' ? $invoice : 'Pending',
            'labels' => $labels,
            'trackingDisplay' => $trackingDisplay,
            'barcodeValue' => $barcodeValue ?: 'Pending',
            'barcodeUri' => $this->barcodeDataUri($barcodeValue ?: '0'),
            'deliveryNumber' => $deliveryNumber,
            'deliveryBarcodeUri' => $this->barcodeDataUri($deliveryNumber),
            'chargeStatus' => $chargeStatus,
            'destination' => $destination,
            'shipperName' => $shipperName,
            'shipperAddress' => $shipperAddress,
            'shipperPhone' => $shipperPhone,
            'consigneeName' => $consigneeName,
            'consigneeAddress' => $consigneeAddress,
            'consigneePhone' => $consigneePhone,
            'weightDim' => $weightDim,
            'packageText' => $description.' ('.$packageCount.' pcs)',
            'packageCount' => $packageCount,
            'totalWeight' => $totalWeight,
            'items' => $items,
            'serviceSummary' => $serviceSummary !== '' ? $serviceSummary : 'Shipping Service',
            'accountNumber' => (string) (session('zion.user.account_number') ?? $payload['account_number'] ?? '-'),
            'createdAt' => $this->readableDate($created),
            'deliveryDate' => is_string($deliveryDate) && $deliveryDate !== 'Pending'
                ? 'by '.$this->readableDate($deliveryDate)
                : 'Pending',
            'paymentStatus' => str_contains(strtoupper($paymentType), 'COLLECT') || str_contains(strtoupper($paymentType), 'DUE')
                ? 'Payment is Due'
                : (str_contains(strtoupper($paymentType), 'PAID') ? 'Paid' : $paymentType),
            'freight' => $freight,
            'tax' => $tax,
            'total' => $total,
            'declaredValue' => $declared,
            'logoPath' => $this->logoPath(),
        ];
    }

    private function logoPath(): string
    {
        foreach ([
            public_path('kay-paolo/assets/logo/kay-paolo.png'),
            public_path('kay-paolo/assets/images/kay-paolo-logo.png'),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    private function formatShipmentNumber(string $raw): string
    {
        $labels = collect(explode(',', $raw))
            ->map(fn ($label) => trim($label))
            ->filter()
            ->values();
        $first = (string) ($labels->first() ?? $raw);
        if (preg_match('/^(.+)-(\d+)\/(\d+)$/', $first, $matches)) {
            return $matches[1].'-'.$matches[3];
        }

        return $first !== '' ? $first : 'Pending';
    }

    private function readableDate(mixed $value): string
    {
        try {
            return \Illuminate\Support\Carbon::parse($value)->format('M d, Y h:i a');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function write(string $path, string $binary): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            File::makeDirectory($directory, 0775, true);
        }

        File::put($path, $binary);
    }

    private function safe(string $value): string
    {
        $token = preg_replace('/[^A-Za-z0-9_-]+/', '', trim($value)) ?? '';

        return $token !== '' ? $token : 'pending';
    }
}
