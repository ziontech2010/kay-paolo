<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmShipmentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     shipmentNumber?: string,
     *     trackingNumber?: string,
     *     packageCount?: int|string,
     *     serviceName?: string,
     *     createdAt?: string,
     *     shipperName?: string,
     *     shipperAddress?: string,
     *     shipperContact?: string,
     *     consigneeName?: string,
     *     consigneeAddress?: string,
     *     consigneeContact?: string,
     *     labelUrl?: string,
     *     receiptUrl?: string,
     *     trackingUrl?: string,
     *     confirmationUrl?: string,
     *     homeUrl?: string,
     *     recipientName?: string,
     * }  $shipment
     */
    public function __construct(public array $shipment = [])
    {
    }

    public function envelope(): Envelope
    {
        $number = $this->shipment['shipmentNumber']
            ?? $this->shipment['trackingNumber']
            ?? 'Pending';

        return new Envelope(
            subject: 'Shipment Confirmation — '.$number.' | Kay Paolo Shipping',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.confirm-shipment',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $shipment = $this->shipment;
        $packageCount = (int) ($shipment['packageCount'] ?? 1);
        $packageLabel = $packageCount === 1 ? '1 package' : $packageCount.' packages';

        return [
            'logoUrl' => asset('kay-paolo/assets/logo/kay-paolo.png'),
            'brandName' => 'Kay Paolo Shipping',
            'supportEmail' => 'info@kaypaoloshipping.com',
            'supportPhone' => '(732) 898-9303',
            'supportAddress' => '414 Main St, Asbury Park, NJ 07712',
            'websiteUrl' => 'https://kaypaoloshipping.com',
            'recipientName' => $shipment['recipientName'] ?? null,
            'shipmentNumber' => $shipment['shipmentNumber'] ?? $shipment['trackingNumber'] ?? 'Pending',
            'trackingNumber' => $shipment['trackingNumber'] ?? $shipment['shipmentNumber'] ?? 'Pending',
            'packageLabel' => $packageLabel,
            'serviceName' => $shipment['serviceName'] ?? 'Shipping Service',
            'createdAt' => $shipment['createdAt'] ?? now()->format('M d, Y'),
            'shipperName' => $shipment['shipperName'] ?? 'Kay Paolo Shipping',
            'shipperAddress' => $shipment['shipperAddress'] ?? '414 Main St, Asbury Park, NJ 07712',
            'shipperContact' => $shipment['shipperContact'] ?? 'info@kaypaoloshipping.com',
            'consigneeName' => $shipment['consigneeName'] ?? 'Destination Customer',
            'consigneeAddress' => $shipment['consigneeAddress'] ?? 'Destination address pending',
            'consigneeContact' => $shipment['consigneeContact'] ?? 'Phone pending',
            'labelUrl' => $shipment['labelUrl'] ?? route('shipment.label'),
            'receiptUrl' => $shipment['receiptUrl'] ?? route('shipment.receipt'),
            'trackingUrl' => $shipment['trackingUrl'] ?? route('tracking'),
            'confirmationUrl' => $shipment['confirmationUrl'] ?? route('shipment.confirmation'),
            'homeUrl' => $shipment['homeUrl'] ?? route('home'),
        ];
    }
}
