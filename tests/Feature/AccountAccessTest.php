<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    public function test_web_login_submit_falls_back_to_kay_paolo_endpoint(): void
    {
        Http::fake([
            '*/api/bocicot/login' => Http::response([
                'message' => 'Not Found',
            ], 404),
            '*/api/kay-paolo/login' => Http::response([
                'message' => 'Logged in Successfully',
                'error' => 'false',
                'token_type' => 'Bearer',
                'access_token' => 'fallback-token',
                'user' => [
                    'id' => 44,
                    'name' => 'Fallback User',
                    'email' => 'fallback@example.com',
                    'role_id' => 2,
                    'role' => ['name' => 'Client'],
                ],
            ]),
        ]);

        $this->post('/login', [
            'email' => 'fallback@example.com',
            'password' => 'password',
        ])
            ->assertRedirect('/')
            ->assertSessionHas('zion.access_token', 'fallback-token')
            ->assertSessionHas('zion.user.email', 'fallback@example.com');
    }

    public function test_account_profile_update_changes_portal_session_contact_details(): void
    {
        Http::fake([
            '*/web-api/update-profile-bocicot' => Http::response([
                'status' => 'success',
                'message' => 'Profile updated.',
            ]),
        ]);

        $this->withSession($this->zionSession())
            ->post('/account/profile', [
                'name' => 'Kay Paolo Admin',
                'email' => 'admin@kaypaoloshipping.com',
                'phone' => '7325550199',
                'address' => '414 Main St',
                'city' => 'Asbury Park',
                'state' => 'NJ',
                'zip' => '07712',
            ])
            ->assertRedirect('/account')
            ->assertSessionHas('zion.user.name', 'Kay Paolo Admin')
            ->assertSessionHas('zion.user.email', 'admin@kaypaoloshipping.com')
            ->assertSessionHas('zion.user.phone', '7325550100')
            ->assertSessionHas('zion.user.shipper_phone', '7325550100')
            ->assertSessionHas('zion.user.shipper_address', '414 Main St');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();

            return str_contains($request->url(), '/web-api/update-profile-bocicot')
                && $request->hasHeader('Authorization', 'Bearer session-token')
                && ($data['phone'] ?? null) === '7325550100'
                && ($data['shipper_phone'] ?? null) === '7325550100';
        });
    }

    public function test_account_exposes_pickup_invoice_security_and_profile_access(): void
    {
        $this->withSession($this->zionSession())
            ->get('/account')
            ->assertOk()
            ->assertSee('id="profileForm"', false)
            ->assertSee('id="profilePhone" name="phone" type="text" value="7325550100" readonly', false)
            ->assertSee('/shipment-history?view=pickup', false)
            ->assertSee('Pickup List', false)
            ->assertSee('Invoices &amp; Receipts', false)
            ->assertSee('Security &amp; Password', false);
    }

    public function test_security_password_contact_url_shows_password_form(): void
    {
        $this->get('/contact?subject=Security%20%2F%20Password')
            ->assertOk()
            ->assertSee('id="passwordForm"', false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Update Password', false)
            ->assertDontSee('id="contactForm"', false);
    }

    public function test_logged_in_agent_can_submit_password_update(): void
    {
        Http::fake([
            '*/web-api/update-password-bocicot' => Http::response([
                'message' => 'Not Found',
            ], 404),
            '*/web-api/change-password-bocicot' => Http::response([
                'message' => 'Not Found',
            ], 404),
            '*/api/bocicot/update-password' => Http::response([
                'status' => 'success',
                'message' => 'Password updated.',
            ]),
        ]);

        $this->withSession($this->zionSession())
            ->post('/account/password', [
                'current_password' => 'old-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('contact', ['subject' => 'Security / Password']))
            ->assertSessionHas('password_status', 'Password updated.');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();

            return str_contains($request->url(), '/api/bocicot/update-password')
                && $request->hasHeader('Authorization', 'Bearer session-token')
                && ($data['email'] ?? null) === 'test@example.com'
                && ($data['current_password'] ?? null) === 'old-password'
                && ($data['new_password'] ?? null) === 'new-secure-password'
                && ($data['password_confirmation'] ?? null) === 'new-secure-password';
        });
    }

    public function test_password_update_without_session_verifies_agent_credentials_first(): void
    {
        Http::fake([
            '*/api/bocicot/login' => Http::response([
                'message' => 'Logged in Successfully',
                'error' => 'false',
                'token_type' => 'Bearer',
                'access_token' => 'fresh-token',
                'user' => [
                    'id' => 12,
                    'name' => 'Fresh Agent',
                    'email' => 'fresh@example.com',
                    'role_id' => 2,
                    'role' => ['name' => 'Agent'],
                ],
            ]),
            '*/web-api/update-password-bocicot' => Http::response([
                'status' => 'success',
                'message' => 'Password updated.',
            ]),
        ]);

        $this->post('/account/password', [
            'email' => 'fresh@example.com',
            'current_password' => 'old-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect(route('contact', ['subject' => 'Security / Password']))
            ->assertSessionHas('zion.access_token', 'fresh-token')
            ->assertSessionHas('password_status', 'Password updated.');
    }

    public function test_quote_shipper_contact_fields_are_editable(): void
    {
        $html = $this->withSession($this->zionSession())
            ->get('/quote-details')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="from_name"', $html);
        $this->assertStringContainsString('id="from_email"', $html);
        $this->assertStringContainsString('id="from_phone"', $html);
        $this->assertStringNotContainsString('id="from_name" value="Test User" readonly', $html);
        $this->assertStringNotContainsString('id="from_email" value="test@example.com" readonly', $html);
        $this->assertStringNotContainsString('id="from_phone" value="7325550100" readonly', $html);
    }

    public function test_https_and_zion_defaults_are_production_ready(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));
        $appScript = file_get_contents(public_path('kay-paolo/assets/app.js'));

        $this->assertStringContainsString('RewriteCond %{HTTPS} !=on', $htaccess);
        $this->assertStringContainsString('https://%{HTTP_HOST}%{REQUEST_URI}', $htaccess);
        $this->assertSame('https://www.zionshipping.com/', config('services.zion_shipping.api_url'));
        $this->assertStringContainsString('https://www.zionshipping.com/', $appScript);
    }

    private function zionSession(): array
    {
        return [
            'zion.access_token' => 'session-token',
            'zion.user' => [
                'id' => 7,
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '7325550100',
                'role_id' => 2,
                'role' => ['name' => 'Client'],
                'account_number' => 'KP1001',
            ],
        ];
    }
}
