<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_public_kay_paolo_pages_render(): void
    {
        foreach ([
            '/login',
            '/quote',
            '/quote-details',
            '/create-shipment',
            '/shipment-history',
            '/tracking',
            '/tracking-detail',
            '/account',
            '/invoice',
            '/receipt',
            '/receipt-a4',
            '/about',
            '/services',
            '/contact',
        ] as $path) {
            $this->get($path)->assertStatus(200);
        }
    }

    public function test_dashboard_renders_without_a_server_side_zion_session(): void
    {
        $this->get('/dashboard')->assertStatus(200);
    }

    public function test_login_form_has_web_fallback_and_api_endpoint(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('action="http://localhost/login"', false)
            ->assertSee('loginPage', false)
            ->assertSee('data-api-endpoint="http://localhost/api/kay-paolo/login"', false)
            ->assertSee('data-api-login', false);

        $this->postJson('/api/kay-paolo/login')->assertStatus(422);
    }

    public function test_api_login_redirects_browser_submits_to_account(): void
    {
        Http::fake([
            '*/api/kay-paolo/login' => Http::response([
                'message' => 'Logged in Successfully',
                'message_type' => 'success',
                'error' => 'false',
                'token_type' => 'Bearer',
                'access_token' => 'fake-token',
                'user' => [
                    'id' => 123,
                    'name' => 'Test User',
                    'role_id' => 2,
                    'role' => ['name' => 'Client'],
                ],
            ]),
        ]);

        $this->post('/api/kay-paolo/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'role_id' => 2,
        ])
            ->assertOk()
            ->assertSee('kayPaoloZionToken', false)
            ->assertSee('window.location.replace("http:\/\/localhost\/account")', false)
            ->assertDontSee('/dashboard', false);
    }

    public function test_layout_exposes_session_token_for_quote_api_calls(): void
    {
        $this->withSession([
            'zion.access_token' => 'session-token',
            'zion.user' => ['name' => 'Session User', 'role_id' => 2],
        ])
            ->get('/quote-details')
            ->assertStatus(200)
            ->assertSee('sessionToken: "session-token"', false)
            ->assertSee('loginPage: "http:\/\/localhost\/login"', false);
    }

    public function test_quote_page_uses_archive_customer_pull_flow(): void
    {
        $this->get('/quote')
            ->assertStatus(200)
            ->assertSee('qCustomerLookup', false)
            ->assertSee('pullCustomerBtn', false)
            ->assertSee('/quote-details', false)
            ->assertDontSee('id="quoteForm"', false);
    }

    public function test_quote_details_exposes_existing_customer_consignee_ui(): void
    {
        $this->get('/quote-details?lookup=9400&customer=7020')
            ->assertStatus(200)
            ->assertSee('value="7020"', false)
            ->assertSee('name="consigneeType"', false)
            ->assertSee('existingConsigneeSelectField', false)
            ->assertSee('existingConsigneeResult', false)
            ->assertSee('id="consignee_id"', false);
    }

    public function test_quote_details_has_flat_rate_dropdown_for_package_blocks(): void
    {
        $this->get('/quote-details?lookup=9400&customer=7020')
            ->assertStatus(200)
            ->assertSee('pkgFlatRate1', false)
            ->assertSee('pkg-flat-rate-field', false)
            ->assertSee('pkgFlatRateType1', false)
            ->assertSee('pkg-flat-rate-type', false);
    }

    public function test_receipt_and_invoice_render_documents_not_raw_json(): void
    {
        foreach (['/receipt', '/invoice', '/receipt-a4'] as $path) {
            $this->get($path)
                ->assertStatus(200)
                ->assertSee('Print', false)
                ->assertDontSee('api-raw', false)
                ->assertDontSee('JSON.stringify', false)
                ->assertDontSee('receiptSummary', false)
                ->assertDontSee('invoicePayload', false);
        }
    }
}
