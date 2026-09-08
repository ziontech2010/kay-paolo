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

        $response = $this->attemptLogin($zion, $payload);
        $data = $response['data'] ?? [];
        $failed = !$this->loginSucceeded($response);

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

    public function updateProfile(Request $request, ZionShippingApi $zion): RedirectResponse
    {
        if (!$request->hasSession() || !session('zion.access_token')) {
            return redirect()->route('login', ['redirect' => route('account', absolute: false)]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:180'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:80'],
            'zip' => ['nullable', 'string', 'max:40'],
        ]);

        $user = session('zion.user', []);
        $response = $this->postWithFallback($zion, [
            ['endpoint' => 'web-api/update-profile-bocicot', 'web' => true],
            ['endpoint' => 'web-api/profile-update-bocicot', 'web' => true],
            ['endpoint' => 'web-api/update-user-profile-bocicot', 'web' => true],
            ['endpoint' => 'bocicot/update-profile'],
            ['endpoint' => 'bocicot/profile-update'],
            ['endpoint' => 'bocicot/update-user-profile'],
            ['endpoint' => 'kay-paolo/update-profile'],
            ['endpoint' => 'kay-paolo/profile-update'],
            ['endpoint' => 'update-profile'],
            ['endpoint' => 'profile-update'],
        ], $this->profileUpdatePayload($validated, $user), session('zion.access_token'), fn (array $response) => $this->profileUpdateFailed($response));

        if ($this->profileUpdateFailed($response) && $this->isProfileUpdateBlockingFailure($response)) {
            return back()
                ->withInput()
                ->withErrors(['profile' => $this->profileUpdateMessage($response)]);
        }

        $user = $this->mergeProfileUser($user, $validated, $response['data'] ?? []);

        $request->session()->put('zion.user', $user);

        return redirect()
            ->route('account')
            ->with('profile_status', $this->profileUpdateFailed($response)
                ? 'Profile updated in this Kay Paolo session. Zion did not confirm the remote profile save.'
                : ($response['data']['message'] ?? 'Profile contact details updated.'));
    }

    public function updatePassword(Request $request, ZionShippingApi $zion): RedirectResponse
    {
        $hasToken = (bool) session('zion.access_token');
        $validated = $request->validate([
            'email' => [$hasToken ? 'nullable' : 'required', 'email', 'max:180'],
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed', 'different:current_password'],
        ]);

        $token = session('zion.access_token');

        if (!$token) {
            $loginResponse = $this->attemptLogin($zion, [
                'email' => $validated['email'],
                'password' => $validated['current_password'],
            ]);

            if (!$this->loginSucceeded($loginResponse)) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => $loginResponse['data']['message'] ?? 'Unable to verify the current agent credentials.']);
            }

            $loginData = $loginResponse['data'];
            $request->session()->regenerate();
            $request->session()->put([
                'zion.access_token' => $loginData['access_token'],
                'zion.token_type' => $loginData['token_type'] ?? 'Bearer',
                'zion.user' => $loginData['user'] ?? [],
            ]);

            $token = $loginData['access_token'];
        }

        $response = $this->postWithFallback($zion, [
            ['endpoint' => 'web-api/update-password-bocicot', 'web' => true],
            ['endpoint' => 'web-api/change-password-bocicot', 'web' => true],
            ['endpoint' => 'bocicot/update-password'],
            ['endpoint' => 'bocicot/change-password'],
            ['endpoint' => 'kay-paolo/update-password'],
            ['endpoint' => 'kay-paolo/change-password'],
            ['endpoint' => 'update-password'],
            ['endpoint' => 'change-password'],
        ], $this->passwordUpdatePayload($validated), $token, fn (array $response) => $this->passwordUpdateFailed($response));

        if ($this->passwordUpdateFailed($response)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['password' => $this->passwordUpdateMessage($response)]);
        }

        return redirect()
            ->route('contact', ['subject' => 'Security / Password'])
            ->with('password_status', $response['data']['message'] ?? 'Password updated successfully.');
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

    private function attemptLogin(ZionShippingApi $zion, array $payload): array
    {
        $response = $zion->post('bocicot/login', $payload);

        if (!$response['ok'] && $this->shouldTryFallback($response)) {
            $response = $zion->post('kay-paolo/login', $payload);
        }

        return $response;
    }

    private function loginSucceeded(array $response): bool
    {
        $data = $response['data'] ?? [];

        return ($response['ok'] ?? false)
            && (($data['error'] ?? 'false') !== 'true')
            && !empty($data['access_token']);
    }

    private function postWithFallback(ZionShippingApi $zion, array $targets, array $payload, ?string $token = null, ?callable $failed = null): array
    {
        $lastResponse = null;
        $failed ??= static fn (array $response): bool => !($response['ok'] ?? false);

        foreach ($targets as $target) {
            $lastResponse = !empty($target['web'])
                ? $zion->postWeb($target['endpoint'], $payload, $token)
                : $zion->post($target['endpoint'], $payload, $token);

            if (($lastResponse['ok'] ?? false) && !$failed($lastResponse)) {
                return $lastResponse;
            }

            if (!$this->shouldTryFallback($lastResponse)) {
                return $lastResponse;
            }
        }

        return $lastResponse ?? [
            'ok' => false,
            'status' => 502,
            'data' => [
                'status' => 'error',
                'message' => 'Unable to reach the shipping API.',
            ],
        ];
    }

    private function profileUpdatePayload(array $validated, array $user): array
    {
        $phone = $this->profilePhone($user);
        $address = $validated['address'] ?? '';
        $city = $validated['city'] ?? '';
        $state = $validated['state'] ?? '';
        $zip = $validated['zip'] ?? '';

        return array_filter([
            'id' => $user['id'] ?? null,
            'user_id' => $user['id'] ?? null,
            'agent_id' => $user['agent_id'] ?? $user['id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $phone !== '' ? $phone : null,
            'mobile' => $phone !== '' ? $phone : null,
            'phone_number' => $phone !== '' ? $phone : null,
            'contact_phone' => $phone !== '' ? $phone : null,
            'shipper_phone' => $phone !== '' ? $phone : null,
            'shipper_phone_1' => $phone !== '' ? $phone : null,
            'address' => $address,
            'shipper_address' => $address,
            'city' => $city,
            'shipper_city' => $city,
            'state' => $state,
            'shipper_state' => $state,
            'zip' => $zip,
            'shipper_zip' => $zip,
        ], static fn ($value) => $value !== null);
    }

    private function mergeProfileUser(array $user, array $validated, array $responseData): array
    {
        $currentPhone = $this->profilePhone($user);
        $remoteUser = $responseData['user']
            ?? $responseData['profile']
            ?? $responseData['data']['user']
            ?? $responseData['data']['profile']
            ?? [];

        if (is_array($remoteUser)) {
            $user = array_merge($user, $remoteUser);
        }

        $phone = $currentPhone !== '' ? $currentPhone : $this->profilePhone($user);
        $address = $validated['address'] ?? '';
        $city = $validated['city'] ?? '';
        $state = $validated['state'] ?? '';
        $zip = $validated['zip'] ?? '';

        $user['name'] = $validated['name'];
        $user['email'] = $validated['email'];
        $user['phone'] = $phone;
        $user['mobile'] = $phone;
        $user['phone_number'] = $phone;
        $user['contact_phone'] = $phone;
        $user['shipper_phone'] = $phone;
        $user['shipper_phone_1'] = $phone;
        $user['address'] = $address;
        $user['shipper_address'] = $address;
        $user['city'] = $city;
        $user['shipper_city'] = $city;
        $user['state'] = $state;
        $user['shipper_state'] = $state;
        $user['zip'] = $zip;
        $user['shipper_zip'] = $zip;

        return $user;
    }

    private function profilePhone(array $user): string
    {
        foreach ([
            'phone',
            'mobile',
            'shipper_phone',
            'shipper_phone_1',
            'phone_number',
            'phoneNumber',
            'mobile_phone',
            'mobileNumber',
            'mobile_number',
            'contact_phone',
            'contactPhone',
            'telephone',
            'telephone_number',
            'shipper_contact',
            'contact',
        ] as $key) {
            $value = trim((string) ($user[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function profileUpdateFailed(array $response): bool
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $status = strtolower((string) ($data['status'] ?? ''));
        $error = strtolower((string) ($data['error'] ?? ''));

        return !($response['ok'] ?? false)
            || in_array($status, ['error', 'failed', 'fail'], true)
            || in_array($error, ['true', '1', 'yes'], true);
    }

    private function isProfileUpdateBlockingFailure(array $response): bool
    {
        return in_array((int) ($response['status'] ?? 0), [401, 403, 422], true);
    }

    private function profileUpdateMessage(array $response): string
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return (string) ($data['message'] ?? $data['error'] ?? 'Unable to update the profile.');
    }

    private function passwordUpdatePayload(array $validated): array
    {
        $user = session('zion.user', []);
        $email = $validated['email'] ?? $user['email'] ?? null;
        $currentPassword = $validated['current_password'];
        $newPassword = $validated['password'];
        $confirmedPassword = $validated['password_confirmation'] ?? $newPassword;

        return array_filter([
            'email' => $email,
            'user_id' => $user['id'] ?? null,
            'agent_id' => $user['agent_id'] ?? $user['id'] ?? null,
            'current_password' => $currentPassword,
            'old_password' => $currentPassword,
            'oldPassword' => $currentPassword,
            'password' => $newPassword,
            'password_confirmation' => $confirmedPassword,
            'new_password' => $newPassword,
            'newPassword' => $newPassword,
            'new_password_confirmation' => $confirmedPassword,
            'confirm_password' => $confirmedPassword,
            'confirmPassword' => $confirmedPassword,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function passwordUpdateFailed(array $response): bool
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $status = strtolower((string) ($data['status'] ?? ''));
        $error = strtolower((string) ($data['error'] ?? ''));

        return !($response['ok'] ?? false)
            || in_array($status, ['error', 'failed', 'fail'], true)
            || in_array($error, ['true', '1', 'yes'], true);
    }

    private function passwordUpdateMessage(array $response): string
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        if (in_array((int) ($response['status'] ?? 0), [404, 405], true)) {
            return 'Unable to update the password because the connected Zion password endpoint is not available.';
        }

        return (string) ($data['message'] ?? $data['error'] ?? 'Unable to update the password.');
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
