<?php

namespace App\Http\Controllers;

use App\Services\ZionShippingApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZionApiProxyController extends Controller
{
    public function __construct(private readonly ZionShippingApi $zion)
    {
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
        $token = session('zion.access_token');

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
        $status = $response['status'] > 0 ? $response['status'] : 502;

        return response()->json($response['data'], $status);
    }
}
