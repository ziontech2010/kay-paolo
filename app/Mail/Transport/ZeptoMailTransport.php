<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class ZeptoMailTransport extends AbstractTransport
{
    public function __construct(
        protected string $token,
        protected string $host = 'api.zeptomail.com',
        protected ?string $bounceAddress = null,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();
        $from = $envelope->getSender();

        $payload = array_filter([
            'from' => [
                'address' => $from->getAddress(),
                'name' => $from->getName() ?: config('mail.from.name'),
            ],
            'to' => $this->formatAddresses($this->getRecipients($email, $envelope)),
            'cc' => $this->formatAddresses($email->getCc()),
            'bcc' => $this->formatAddresses($email->getBcc()),
            'reply_to' => $this->formatReplyTo($email->getReplyTo()),
            'subject' => $email->getSubject(),
            'htmlbody' => $email->getHtmlBody() ?: nl2br(e((string) $email->getTextBody())),
            'textbody' => $email->getTextBody(),
            'bounce_address' => $this->bounceAddress,
        ], static fn ($value) => $value !== null && $value !== [] && $value !== '');

        $authorization = str_starts_with(strtolower($this->token), 'zoho-enczapikey')
            ? $this->token
            : 'Zoho-enczapikey '.$this->token;

        $host = preg_replace('#^https?://#i', '', $this->host) ?: 'api.zeptomail.com';

        $response = Http::baseUrl('https://'.$host)
            ->withHeaders([
                'Authorization' => $authorization,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post('/v1.1/email', $payload);

        if (! $response->successful()) {
            $error = $response->json('message')
                ?? $response->json('error.message')
                ?? $response->json('data.error')
                ?? $response->body()
                ?: 'Unknown ZeptoMail error';

            throw new \Symfony\Component\Mailer\Exception\TransportException(
                sprintf('ZeptoMail API request failed (%s): %s', $response->status(), is_string($error) ? $error : json_encode($error)),
                $response->status()
            );
        }

        $requestId = $response->json('request_id')
            ?? $response->json('data.request_id')
            ?? $response->json('message')
            ?? null;

        if ($requestId) {
            $email->getHeaders()->addTextHeader('X-ZeptoMail-Request-Id', (string) $requestId);
        }
    }

    /**
     * @param  Address[]  $addresses
     * @return array<int, array{email_address: array{address: string, name?: string}}>
     */
    protected function formatAddresses(array $addresses): array
    {
        return array_values(array_map(static function (Address $address) {
            $entry = ['address' => $address->getAddress()];

            if ($address->getName() !== '') {
                $entry['name'] = $address->getName();
            }

            return ['email_address' => $entry];
        }, $addresses));
    }

    /**
     * @param  Address[]  $addresses
     * @return array<int, array{address: string, name?: string}>|null
     */
    protected function formatReplyTo(array $addresses): ?array
    {
        if ($addresses === []) {
            return null;
        }

        return array_values(array_map(static function (Address $address) {
            $entry = ['address' => $address->getAddress()];

            if ($address->getName() !== '') {
                $entry['name'] = $address->getName();
            }

            return $entry;
        }, $addresses));
    }

    /**
     * @return Address[]
     */
    protected function getRecipients(\Symfony\Component\Mime\Email $email, \Symfony\Component\Mailer\Envelope $envelope): array
    {
        return array_filter($envelope->getRecipients(), static function (Address $address) use ($email) {
            return ! in_array($address, array_merge($email->getCc(), $email->getBcc()), true);
        });
    }

    public function __toString(): string
    {
        return 'zeptomail';
    }
}
