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

    public function postWeb(string $endpoint, array $payload = [], ?string $token = null): array
    {
        return $this->request('post', $endpoint, $payload, $token, true);
    }

    public function get(string $endpoint, array $query = [], ?string $token = null): array
    {
        return $this->request('get', $endpoint, $query, $token);
    }

    public function getWeb(string $endpoint, array $query = [], ?string $token = null): array
    {
        return $this->request('get', $endpoint, $query, $token, true);
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

    private function request(string $method, string $endpoint, array $payload = [], ?string $token = null, bool $webPath = false): array
    {
        $client = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout((int) config('services.zion_shipping.timeout', 45));

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
