<?php

namespace App\Services;

use App\Mail\ConfirmShipmentMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ShipmentConfirmationMailer
{
    /**
     * @param  array<string, mixed>  $shipment
     * @return array{status: string, email: string, mailer?: string, message?: string, error?: string|null}
     */
    public function send(string $email, array $shipment): array
    {
        $email = strtolower(trim($email));
        $mailer = $this->mailerName();

        if ($mailer === null) {
            Log::error('Kay Paolo confirmation email is not configured.', [
                'email' => $email,
            ]);

            return [
                'status' => 'error',
                'email' => $email,
                'message' => 'Shipment confirmation email is not configured for delivery.',
            ];
        }

        try {
            Mail::mailer($mailer)->to($email)->send(new ConfirmShipmentMail($shipment));
        } catch (\Throwable $exception) {
            report($exception);
            Log::error('Kay Paolo confirmation email failed.', [
                'email' => $email,
                'shipment' => $shipment['shipmentNumber'] ?? null,
                'mailer' => $mailer,
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'email' => $email,
                'mailer' => $mailer,
                'message' => 'Unable to send shipment confirmation email.',
                'error' => config('app.debug') ? $exception->getMessage() : null,
            ];
        }

        Log::info('Kay Paolo confirmation email sent.', [
            'from' => 'info@kaypaoloshipping.com',
            'email' => $email,
            'shipment' => $shipment['shipmentNumber'] ?? null,
            'mailer' => $mailer,
        ]);

        return [
            'status' => 'success',
            'email' => $email,
            'mailer' => $mailer,
        ];
    }

    public function mailerName(): ?string
    {
        $token = $this->zeptoToken();
        if ($token !== '') {
            config(['services.zeptomail.token' => $token]);

            return 'zeptomail';
        }

        $defaultMailer = trim((string) (config('mail.default') ?: env('MAIL_MAILER', 'log')));
        $normalizedMailer = strtolower($defaultMailer);

        if ($defaultMailer === '' || in_array($normalizedMailer, ['log', 'array', 'zeptomail'], true)) {
            return null;
        }

        return $defaultMailer;
    }

    public function zeptoToken(): string
    {
        $configured = config('services.zeptomail.token');
        $token = trim((string) $configured);

        if ($token === '') {
            $token = trim((string) (getenv('ZEPTOMAIL_TOKEN') ?: env('ZEPTOMAIL_TOKEN') ?: ''));
        }

        return trim($token, " \t\n\r\0\x0B\"'");
    }
}
