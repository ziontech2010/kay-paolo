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
        ]);

        $payload = array_filter($credentials, static function ($value) {
            return $value !== null && $value !== '';
        });

        $response = $zion->post('kay-paolo/login', $payload);
        $data = $response['data'] ?? [];
        $failed = !$response['ok']
            || (($data['error'] ?? 'false') === 'true')
            || empty($data['access_token']);

        if ($failed) {
            $message = $data['message'] ?? 'Unable to log in to Kay Paolo.';

            if (!$request->hasSession()) {
                return redirect()->route('login', ['login_error' => $message]);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $message]);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->put([
                'zion.access_token' => $data['access_token'],
                'zion.token_type' => $data['token_type'] ?? 'Bearer',
                'zion.user' => $data['user'] ?? [],
            ]);
        }

        $redirectTo = $request->input('redirect');

        if (is_string($redirectTo) && str_starts_with($redirectTo, '/') && !str_starts_with($redirectTo, '//')) {
            return redirect()->to($redirectTo);
        }

        return redirect()->route('home');
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->hasSession()) {
            $request->session()->forget('zion');
        }

        return redirect()->route('home');
    }

    public function dashboard(): View
    {
        return view('pages.dashboard', [
            'zionUser' => session('zion.user', []),
        ]);
    }
}
