<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ShipmentDocumentStore
{
    public function labelPath(string $invoice, string $suffix = ''): string
    {
        $safeInvoice = $this->safeToken($invoice);
        $suffix = $suffix !== '' ? '_'.$this->safeToken($suffix) : '';

        return public_path('label/label_'.$safeInvoice.$suffix.'.pdf');
    }

    public function receiptPath(string $invoice): string
    {
        $safeInvoice = $this->safeToken($invoice);

        return public_path('receipts/receipt_'.$safeInvoice.'.pdf');
    }

    public function labelUrl(string $invoice, string $suffix = ''): string
    {
        $safeInvoice = $this->safeToken($invoice);
        $suffix = $suffix !== '' ? '_'.$this->safeToken($suffix) : '';

        return url('label/label_'.$safeInvoice.$suffix.'.pdf');
    }

    public function receiptUrl(string $invoice): string
    {
        $safeInvoice = $this->safeToken($invoice);

        return url('receipts/receipt_'.$safeInvoice.'.pdf');
    }

    public function hasLabel(string $invoice, string $suffix = ''): bool
    {
        $path = $this->labelPath($invoice, $suffix);

        return is_file($path) && filesize($path) > 4;
    }

    public function hasReceipt(string $invoice): bool
    {
        $path = $this->receiptPath($invoice);

        return is_file($path) && filesize($path) > 4;
    }

    public function saveLabel(string $invoice, string $pdfBinary, string $suffix = ''): string
    {
        return $this->writePdf($this->labelPath($invoice, $suffix), $pdfBinary);
    }

    public function saveReceipt(string $invoice, string $pdfBinary): string
    {
        return $this->writePdf($this->receiptPath($invoice), $pdfBinary);
    }

    public function resolveInvoice(array $query): string
    {
        $candidates = [
            $query['invoice'] ?? null,
            $query['invoice_num'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        $tracking = trim((string) ($query['id'] ?? $query['tracking'] ?? $query['tracking_number'] ?? ''));
        if ($tracking === '') {
            return '';
        }

        if (preg_match('/([A-Za-z]*)(\d{4,})/', $tracking, $matches)) {
            return $matches[2];
        }

        return $tracking;
    }

    private function writePdf(string $path, string $pdfBinary): string
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, $pdfBinary);

        return $path;
    }

    private function safeToken(string $value): string
    {
        $token = preg_replace('/[^A-Za-z0-9_-]+/', '', trim($value)) ?? '';

        return $token !== '' ? $token : 'pending';
    }
}
