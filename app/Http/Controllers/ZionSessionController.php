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

        $response = $zion->post('bocicot/login', $payload);

        if (!$response['ok'] && $this->shouldTryFallback($response)) {
            $response = $zion->post('kay-paolo/login', $payload);
        }

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

    public function updateProfile(Request $request): RedirectResponse
    {
        if (!$request->hasSession() || !session('zion.access_token')) {
            return redirect()->route('login', ['redirect' => route('account', absolute: false)]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:80'],
            'zip' => ['nullable', 'string', 'max:40'],
        ]);

        $user = session('zion.user', []);
        $user['name'] = $validated['name'];
        $user['email'] = $validated['email'];
        $user['phone'] = $validated['phone'] ?? '';
        $user['mobile'] = $validated['phone'] ?? '';
        $user['address'] = $validated['address'] ?? '';
        $user['shipper_address'] = $validated['address'] ?? '';
        $user['city'] = $validated['city'] ?? '';
        $user['shipper_city'] = $validated['city'] ?? '';
        $user['state'] = $validated['state'] ?? '';
        $user['shipper_state'] = $validated['state'] ?? '';
        $user['zip'] = $validated['zip'] ?? '';
        $user['shipper_zip'] = $validated['zip'] ?? '';

        $request->session()->put('zion.user', $user);

        return redirect()
            ->route('account')
            ->with('profile_status', 'Profile contact details updated for this Kay Paolo session.');
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

    private function shouldTryFallback(array $response): bool
    {
        $status = (int) ($response['status'] ?? 0);
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $message = strtolower((string) ($data['message'] ?? ''));
        $error = strtolower((string) ($data['error'] ?? ''));
        $appLocked = strtolower((string) ($data['app_locked'] ?? ''));

        return $status === 0
            || in_array($status, [404, 405], true)
            || $status >= 500
            || str_contains($message, 'session store not set')
            || str_contains($message, 'app is locked')
            || ($error === 'true' && $appLocked === 'true')
            || isset($data['html']);
    }
}
