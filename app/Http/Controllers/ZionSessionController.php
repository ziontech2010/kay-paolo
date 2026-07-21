<?php

namespace App\Http\Controllers;

use App\Services\ZionShippingApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZionSessionController extends Controller
{
    public function showLogin(): View
    {
        return view('pages.login');
    }

    public function login(Request $request, ZionShippingApi $zion): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role_id' => ['nullable', 'integer'],
        ]);

        $response = $zion->post('kay-paolo/login', array_filter($credentials, static function ($value) {
            return $value !== null && $value !== '';
        }));

        $payload = $response['data'];
        $failed = !$response['ok'] || (($payload['error'] ?? 'false') === 'true');

        if ($failed) {
            return back()
                ->withInput($request->only('email', 'role_id'))
                ->withErrors(['email' => $payload['message'] ?? 'Unable to log in with Zion Shipping.']);
        }

        session([
            'zion.access_token' => $payload['access_token'] ?? null,
            'zion.token_type' => $payload['token_type'] ?? 'Bearer',
            'zion.user' => $payload['user'] ?? [],
            'zion.session_id' => $payload['session_id'] ?? null,
            'zion.csrf_token' => $payload['csrf_token'] ?? null,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('zion');

        return redirect()->route('home');
    }

    public function dashboard(): View
    {
        return view('pages.dashboard', [
            'zionUser' => session('zion.user', []),
        ]);
    }
}
