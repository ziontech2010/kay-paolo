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

        if ($labels) {
            return $labels;
        }

        $invoice = $this->resolveInvoice($query);
        if ($invoice !== '') {
            return ['HTS'.$invoice.'-1/1'];
        }

        return ['Pending'];
    }

    public function ensureLabelPdf(array $query, array $shipment = []): string
    {
        $invoice = $this->resolveInvoice($query) ?: 'pending';
        $path = $this->labelPath($invoice);
        // Always rebuild labels so layout/template updates are never stuck behind a stale file.
        $payload = $this->documentPayload($query, $shipment);
        // Match Zion 4x6 label stock (384x576 DomPDF points).
        $pdf = Pdf::loadView('documents.pdf.label', $payload)->setPaper([0, 0, 384, 576], 'portrait');
        $this->write($path, $pdf->output());
        $this->mirrorPublicCopy($path, 'label');

        return $path;
    }

    public function ensureReceiptPdf(array $query, array $shipment = []): string
    {
        $invoice = $this->resolveInvoice($query) ?: 'pending';
        $path = $this->receiptPath($invoice);
        $force = !empty($query['regen'])
            || $this->shipmentHasPackageDetails($shipment)
            || $this->templateIsNewer('documents/pdf/receipt.blade.php', $path);

        if (is_file($path) && filesize($path) > 4 && !$force) {
            return $path;
        }

        $payload = $this->documentPayload($query, $shipment);
        $pdf = Pdf::loadView('documents.pdf.receipt', $payload)->setPaper('a4', 'portrait');
        $this->write($path, $pdf->output());
        $this->mirrorPublicCopy($path, 'receipts');

        return $path;
    }

    public function invoiceFromFilename(string $filename, string $prefix): string
    {
        $safeName = basename($filename);
        if (!preg_match('/^'.preg_quote($prefix, '/').'_([A-Za-z0-9_-]+)\.pdf$/', $safeName, $matches)) {
            return '';
        }

        return $matches[1];
    }

    public function barcodeDataUri(string $value, int $height = 50, int $widthFactor = 2): string
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
            $png = $generator->getBarcode(
                $value,
                $generator::TYPE_CODE_128,
                max(1, $widthFactor),
                max(20, $height)
            );

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

        $invoice = $this->resolveInvoice($query);
        $labels = $this->labelNumbers($query);
        if ($labels === ['Pending'] || $labels === []) {
            $trackingFallback = (string) ($payload['tracking_number']
                ?? $shipping['tracking_number']
                ?? $response['tracking_number']
                ?? $responseData['tracking_number']
                ?? '');
            if ($trackingFallback !== '') {
                $labels = $this->labelNumbers(['id' => $trackingFallback]);
            } elseif ($invoice !== '') {
                $labels = ['HTS'.$invoice.'-1/1'];
            }
        }
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

        $description = (string) ($this->firstNonEmptyString([
            $payload['package_description'] ?? null,
            $shipping['package_description'] ?? null,
            $response['package_description'] ?? null,
            $responseData['package_description'] ?? null,
        ]) ?: 'Package');

        $dimensions = $this->firstNonEmptyArray([
            $payload['dimensions'] ?? null,
            $shipping['dimensions'] ?? null,
            $response['dimensions'] ?? null,
            $responseData['dimensions'] ?? null,
        ]);
        $packages = $this->firstNonEmptyArray([
            $payload['packages'] ?? null,
            $payload['package'] ?? null,
            $shipping['packages'] ?? null,
            $shipping['package'] ?? null,
            $response['packages'] ?? null,
            $response['package'] ?? null,
            $responseData['packages'] ?? null,
            $responseData['package'] ?? null,
        ]);

        $items = $this->buildPackageItems($packages, $dimensions, $description, $labels);
        $packageCount = max(
            1,
            (int) ($payload['package_count'] ?? $shipping['package_count'] ?? $response['package_count'] ?? 0),
            count($labels),
            array_sum(array_map(static fn ($item) => (int) ($item['count'] ?? 1), $items))
        );
        $totalWeight = array_sum(array_map(static fn ($item) => (float) ($item['weight'] ?? 0), $items)) ?: 1;
        $first = $items[0] ?? ['weight' => 1, 'dimensions' => '1 x 1 x 1'];
        $weightDim = number_format((float) $first['weight'], 0).' lbs, DIM: ('.strtoupper(str_replace(' x ', ' X ', (string) $first['dimensions'])).')';

        $freight = (float) ($selected['freight'] ?? $response['freight'] ?? $shipping['freight'] ?? $selected['total'] ?? $selected['price'] ?? $payload['deliveryEstimatePrice'] ?? 0);
        $tax = (float) ($selected['tax'] ?? $response['tax'] ?? $shipping['tax'] ?? 0);
        $total = (float) ($selected['total'] ?? $selected['price'] ?? $payload['deliveryEstimatePrice'] ?? $response['total'] ?? $shipping['total'] ?? ($freight + $tax));
        $declared = (float) ($payload['total_value'] ?? $payload['package_value'] ?? $shipping['total_value'] ?? 0);
        $deliveryOption = (string) ($payload['delivery_option'] ?? $payload['selected_shipper'] ?? $shipping['delivery_option'] ?? $shipping['selected_shipper'] ?? $selected['service'] ?? 'Shipping Service');
        $deliveryLocation = (string) ($payload['delivery_location'] ?? $shipping['delivery_location'] ?? '');
        $serviceSummary = trim($deliveryOption.($deliveryLocation !== '' ? ' | '.$deliveryLocation : ''));
        $created = $response['created_at'] ?? $shipping['created_at'] ?? $responseData['created_at'] ?? now()->toDateTimeString();
        $deliveryDate = $payload['deliveryEstimateDate'] ?? $selected['eta'] ?? $response['delivery_date'] ?? $shipping['delivery_date'] ?? 'Pending';

        // Prefer shipper/consignee fields from shipping history when payload is empty.
        if ($shipperName === 'Kay Paolo Shipping' && !empty($shipping['shipper_name'])) {
            $shipperName = (string) $shipping['shipper_name'];
        }
        if (str_contains($shipperAddress, 'Asbury Park') && !empty($shipping['shipper_address'])) {
            $shipperAddress = trim(implode("\n", array_filter([
                $shipping['shipper_address'] ?? null,
                trim(implode(' ', array_filter([
                    $shipping['shipper_city'] ?? null,
                    $shipping['shipper_state'] ?? null,
                    $shipping['shipper_zip'] ?? null,
                ]))),
                $shipping['shipper_country'] ?? null,
            ])));
        }
        if ($consigneeName === 'Destination Customer' && !empty($shipping['consignee_name'])) {
            $consigneeName = (string) $shipping['consignee_name'];
        }
        if (str_contains($consigneeAddress, 'pending') && !empty($shipping['consignee_address'])) {
            $consigneeAddress = trim(implode("\n", array_filter([
                $shipping['consignee_address'] ?? null,
                trim(implode(' ', array_filter([
                    $shipping['consignee_city'] ?? null,
                    $shipping['consignee_state'] ?? null,
                    $shipping['consignee_zip'] ?? null,
                ]))),
                $shipping['consignee_country'] ?? null,
            ])));
        }

        return [
            'invoice' => $invoice !== '' ? $invoice : 'Pending',
            'labels' => $labels,
            'trackingDisplay' => $trackingDisplay,
            'barcodeValue' => $barcodeValue ?: 'Pending',
            'barcodeUri' => $this->barcodeDataUri($barcodeValue ?: '0', 70, 3),
            'scanBarcodeUri' => $this->barcodeDataUri($barcodeValue ?: '0', 110, 3),
            'deliveryNumber' => $deliveryNumber,
            'deliveryBarcodeUri' => $this->barcodeDataUri($deliveryNumber, 48, 2),
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
            'accountNumber' => (string) (session('zion.user.account_number') ?? $payload['account_number'] ?? $shipping['account_number'] ?? '-'),
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

    private function buildPackageItems(array $packages, array $dimensions, string $description, array $labels): array
    {
        $items = [];

        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            $dims = $package['dimensions'] ?? [];
            if (!is_array($dims)) {
                $dims = [];
            }

            $length = (float) ($dims['length'] ?? $package['length'] ?? 1);
            $width = (float) ($dims['width'] ?? $package['width'] ?? 1);
            $height = (float) ($dims['height'] ?? $package['height'] ?? 1);
            $weight = (float) ($package['weight'] ?? 1);
            $count = max(1, (int) ($package['package_count'] ?? $package['count'] ?? $package['qty'] ?? 1));

            $items[] = [
                'pieces' => str_pad((string) $count, 2, '0', STR_PAD_LEFT),
                'count' => $count,
                'description' => (string) ($package['description'] ?? $package['package_description'] ?? $description),
                'weight' => $weight,
                'volume' => number_format(($length * $width * $height) / 1728, 2, '.', ''),
                'dimensions' => $length.' x '.$width.' x '.$height,
            ];
        }

        if ($items) {
            return $items;
        }

        $weights = array_values((array) ($dimensions['weight'] ?? []));
        $lengths = array_values((array) ($dimensions['length'] ?? []));
        $widths = array_values((array) ($dimensions['width'] ?? []));
        $heights = array_values((array) ($dimensions['height'] ?? []));
        $counts = array_values((array) ($dimensions['package_count_ind'] ?? []));
        $rowCount = max(count($weights), count($lengths), count($widths), count($heights), count($counts), count($labels), 1);

        for ($i = 0; $i < $rowCount; $i++) {
            $length = (float) ($lengths[$i] ?? 1);
            $width = (float) ($widths[$i] ?? 1);
            $height = (float) ($heights[$i] ?? 1);
            $count = max(1, (int) ($counts[$i] ?? 1));
            $items[] = [
                'pieces' => str_pad((string) $count, 2, '0', STR_PAD_LEFT),
                'count' => $count,
                'description' => $description,
                'weight' => (float) ($weights[$i] ?? 1),
                'volume' => number_format(($length * $width * $height) / 1728, 2, '.', ''),
                'dimensions' => $length.' x '.$width.' x '.$height,
            ];
        }

        return $items;
    }

    public function shipmentHasPackageDetails(array $shipment): bool
    {
        $payload = $shipment['payload'] ?? [];
        $response = $shipment['response'] ?? $shipment;
        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
        $shipping = $response['shipping_data'] ?? $response['shipping'] ?? $responseData['shipping'] ?? [];

        foreach ([$payload, $shipping, $response, $responseData] as $source) {
            if (!is_array($source)) {
                continue;
            }
            if ($this->firstNonEmptyArray([
                $source['packages'] ?? null,
                $source['package'] ?? null,
                $source['dimensions'] ?? null,
            ])) {
                return true;
            }
            if ($this->firstNonEmptyString([
                $source['package_description'] ?? null,
                isset($source['package_count']) ? (string) $source['package_count'] : null,
            ]) !== null) {
                return true;
            }
        }

        return false;
    }

    private function firstNonEmptyArray(array $candidates): array
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate)) {
                $decoded = json_decode($candidate, true);
                $candidate = is_array($decoded) ? $decoded : null;
            }
            if (is_array($candidate) && $candidate !== []) {
                return $candidate;
            }
        }

        return [];
    }

    private function firstNonEmptyString(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
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

    private function templateIsNewer(string $relativeView, string $pdfPath): bool
    {
        if (!is_file($pdfPath)) {
            return true;
        }

        $template = resource_path('views/'.$relativeView);
        if (!is_file($template)) {
            return false;
        }

        return filemtime($template) > filemtime($pdfPath);
    }

    private function write(string $path, string $binary): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            File::makeDirectory($directory, 0775, true);
        }

        File::put($path, $binary);
    }

    private function mirrorPublicCopy(string $storagePath, string $directory): void
    {
        try {
            $publicDir = public_path($directory);
            if (!is_dir($publicDir)) {
                File::makeDirectory($publicDir, 0775, true);
            }
            File::copy($storagePath, $publicDir.DIRECTORY_SEPARATOR.basename($storagePath));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function safe(string $value): string
    {
        $token = preg_replace('/[^A-Za-z0-9_-]+/', '', trim($value)) ?? '';

        return $token !== '' ? $token : 'pending';
    }
}
