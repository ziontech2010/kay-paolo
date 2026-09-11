<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ZionShippingApi
{
    public function post(string $endpoint, array $payload = [], ?string $token = null, ?int $timeout = null): array
    {
        return $this->request('post', $endpoint, $payload, $token, false, $timeout);
    }

    public function postMultipart(string $endpoint, array $fields = [], array $files = [], ?string $token = null, ?int $timeout = null): array
    {
        $client = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout($timeout ?? (int) config('services.zion_shipping.timeout', 45));

        if ($token) {
            $client = $client->withToken($token);
        }

        foreach ($files as $file) {
            if (!is_array($file) || empty($file['contents'])) {
                continue;
            }

            $client = $client->attach(
                (string) ($file['name'] ?? 'photos[]'),
                $file['contents'],
                (string) ($file['filename'] ?? 'photo.jpg'),
                array_filter([
                    'Content-Type' => $file['content_type'] ?? null,
                ])
            );
        }

        $payload = [];
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = array_values($value);
                continue;
            }

            if ($value === null) {
                continue;
            }

            $payload[$key] = $value;
        }

        try {
            $response = $client->post($this->endpointPath($endpoint, false), $payload);
        } catch (ConnectionException $exception) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => [
                    'status' => 'error',
                    'message' => 'Unable to reach the shipping API.',
                ],
            ];
        }

        return $this->formatResponse($response);
    }

    public function postWeb(string $endpoint, array $payload = [], ?string $token = null, ?int $timeout = null): array
    {
        return $this->request('post', $endpoint, $payload, $token, true, $timeout);
    }

    public function get(string $endpoint, array $query = [], ?string $token = null, ?int $timeout = null): array
    {
        return $this->request('get', $endpoint, $query, $token, false, $timeout);
    }

    public function getWeb(string $endpoint, array $query = [], ?string $token = null, ?int $timeout = null): array
    {
        return $this->request('get', $endpoint, $query, $token, true, $timeout);
    }

    public function getRaw(string $endpoint, array $query = [], ?string $token = null, bool $webPath = false, ?int $timeout = null): ?Response
    {
        $client = Http::baseUrl($this->baseUrl())
            ->timeout($timeout ?? (int) config('services.zion_shipping.timeout', 45));

        if ($token) {
            $client = $client->withToken($token);
        }

        try {
            return $client->get($this->endpointPath($endpoint, $webPath), $query);
        } catch (ConnectionException $exception) {
            return null;
        }
    }

    public function webUrl(string $path, array $query = []): string
    {
        $url = rtrim((string) config('services.zion_shipping.web_url'), '/').'/'.ltrim($path, '/');

        return empty($query) ? $url : $url.'?'.http_build_query($query);
    }

    public function endpointPath(string $endpoint, bool $webPath = false): string
    {
        $endpoint = ltrim($endpoint, '/');

        if ($webPath) {
            return $endpoint;
        }

        if (str_starts_with($endpoint, 'api/')) {
            return $endpoint;
        }

        $basePath = trim((string) parse_url($this->baseUrl(), PHP_URL_PATH), '/');
        if ($basePath === 'api' || str_ends_with($basePath, '/api')) {
            return $endpoint;
        }

        return 'api/'.$endpoint;
    }

    private function request(string $method, string $endpoint, array $payload = [], ?string $token = null, bool $webPath = false, ?int $timeout = null): array
    {
        $client = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout($timeout ?? (int) config('services.zion_shipping.timeout', 45));

        if ($token) {
            $client = $client->withToken($token);
        }

        try {
            $response = $method === 'get'
                ? $client->get($this->endpointPath($endpoint, $webPath), $payload)
                : $client->asJson()->post($this->endpointPath($endpoint, $webPath), $payload);
        } catch (ConnectionException $exception) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => [
                    'status' => 'error',
                    'message' => 'Unable to reach the shipping API.',
                ],
            ];
        }

        return $this->formatResponse($response);
    }

    private function formatResponse(Response $response): array
    {
        $data = $response->json();

        if (!is_array($data)) {
            $body = trim($response->body());
            $data = [
                'status' => $response->successful() ? 'success' : 'error',
                'message' => $body ?: $response->reason(),
            ];

            if ($body !== '' && str_contains($body, '<')) {
                $data['html'] = $body;
            }
        }

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $data,
        ];
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.zion_shipping.api_url'), '/');
    }
}
