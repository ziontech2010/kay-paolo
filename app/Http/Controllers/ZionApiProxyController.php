<?php

namespace App\Http\Controllers;

use App\Services\ZionShippingApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ZionApiProxyController extends Controller
{
    public function __construct(private readonly ZionShippingApi $zion)
    {
    }

    public function login(Request $request): JsonResponse|RedirectResponse|Response
    {
        $payload = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role_id' => ['nullable', 'integer'],
        ]);

        $response = $this->zion->post('kay-paolo/login', array_filter($payload, static function ($value) {
            return $value !== null && $value !== '';
        }));

        if ($request->expectsJson()) {
            return $this->jsonResponse($response);
        }

        $data = $response['data'] ?? [];
        $failed = !$response['ok']
            || (($data['error'] ?? 'false') === 'true')
            || empty($data['access_token']);

        if ($failed) {
            return redirect()->route('login', ['login_error' => $data['message'] ?? 'Unable to log in with Zion Shipping.']);
        }

        return response($this->browserRedirectScript($data))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function fetchUserForQuote(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/fetch-user-for-quote', $request);
    }

    public function consigneeList(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/consignee-list', $request);
    }

    public function flatRates(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/get-flat-rates', $request);
    }

    public function saveConsignee(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/save-consignee', $request);
    }

    public function quote(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/get-quote-result', $request);
    }

    public function createShipment(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/update-shipping', $request);
    }

    public function shippingHistory(Request $request): JsonResponse
    {
        return $this->forwardAuthenticated('kay-paolo/shipping-history-filter', $request);
    }

    public function tracking(Request $request): JsonResponse
    {
        return $this->forward('kay-paolo/validate-tracking', $request);
    }

    private function forwardAuthenticated(string $endpoint, Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please login to Kay Paolo with your Zion Shipping account first.',
            ], 401);
        }

        return $this->forward($endpoint, $request, $token);
    }

    private function forward(string $endpoint, Request $request, ?string $token = null): JsonResponse
    {
        $response = $this->zion->post($endpoint, $request->except('_token'), $token);

        return $this->jsonResponse($response);
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
        $dashboard = json_encode(route('dashboard'));

        return <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Redirecting...</title></head>
<body>
<script>
window.localStorage.setItem('kayPaoloZionToken', {$token});
window.localStorage.setItem('kayPaoloZionUser', JSON.stringify({$user}));
window.location.replace({$dashboard});
</script>
</body>
</html>
HTML;
    }
}
