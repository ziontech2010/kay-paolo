<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ZionShippingApi
{
    public function post(string $endpoint, array $payload = [], ?string $token = null): array
    {
        return $this->request('post', $endpoint, $payload, $token);
    }

    public function get(string $endpoint, array $query = [], ?string $token = null): array
    {
        return $this->request('get', $endpoint, $query, $token);
    }

    public function endpointPath(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');

        if (str_starts_with($endpoint, 'api/')) {
            return $endpoint;
        }

        $basePath = trim((string) parse_url($this->baseUrl(), PHP_URL_PATH), '/');
        if ($basePath === 'api' || str_ends_with($basePath, '/api')) {
            return $endpoint;
        }

        return 'api/'.$endpoint;
    }

    private function request(string $method, string $endpoint, array $payload = [], ?string $token = null): array
    {
        $client = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout((int) config('services.zion_shipping.timeout', 45));

        if ($token) {
            $client = $client->withToken($token);
        }

        try {
            $response = $method === 'get'
                ? $client->get($this->endpointPath($endpoint), $payload)
                : $client->asJson()->post($this->endpointPath($endpoint), $payload);
        } catch (ConnectionException $exception) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => [
                    'status' => 'error',
                    'message' => 'Unable to reach Zion Shipping API.',
                ],
            ];
        }

        return $this->formatResponse($response);
    }

    private function formatResponse(Response $response): array
    {
        $data = $response->json();

        if (!is_array($data)) {
            $data = [
                'status' => $response->successful() ? 'success' : 'error',
                'message' => trim($response->body()) ?: $response->reason(),
            ];
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
