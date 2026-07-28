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
            '/shipment-confirmation',
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

    public function test_shipment_confirmation_page_and_legacy_redirects_render(): void
    {
        $this->get('/shipment-confirmation')
            ->assertStatus(200)
            ->assertSee('data-shipment-confirmation', false)
            ->assertSee('Shipment Confirmed', false)
            ->assertSee('Open Receipt', false)
            ->assertSee('Open A4 Receipt', false)
            ->assertSee('kayPaoloMarkConfirmationSeen', false);

        $this->get('/receipt')
            ->assertStatus(200)
            ->assertSee('kayPaoloReceiptConfirmationGuard', false)
            ->assertSee('kayPaoloShipmentConfirmationSeen', false);

        $this->get('/confirmation.html')
            ->assertRedirect('/shipment-confirmation');

        $this->get('/shipment-confirmation.html')
            ->assertRedirect('/shipment-confirmation');
    }

    public function test_header_shows_my_profile_for_logged_in_users(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('data-auth-link>Login</a>', false);

        $this->withSession([
            'zion.access_token' => 'session-token',
            'zion.user' => ['name' => 'Session User', 'role_id' => 2],
        ])
            ->get('/')
            ->assertStatus(200)
            ->assertSee('data-auth-link>My Profile</a>', false)
            ->assertDontSee('data-auth-link>Account</a>', false);
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
            ->assertSee('pkg-flat-rate-type', false)
            ->assertSee('name="flat_rate[]"', false)
            ->assertSee('name="shipment_type[]"', false);
    }

    public function test_quote_details_matches_bocicot_delivery_and_party_layout(): void
    {
        $this->get('/quote-details?lookup=9400&customer=7020')
            ->assertStatus(200)
            ->assertSee('quote-party-card', false)
            ->assertSee('data-go-back', false)
            ->assertSee('<option value="Pickup in Office">Pickup in Office</option>', false)
            ->assertSee('<option value="Home Delivery">Home Delivery</option>', false)
            ->assertSee('<option value="100">100</option>', false)
            ->assertDontSee('id="packageDescription"', false)
            ->assertDontSee('Door to Door', false)
            ->assertDontSee('Port to Port', false)
            ->assertDontSee('General merchandise', false);
    }

    public function test_create_shipment_shows_selected_service_controls_without_description_default(): void
    {
        $this->get('/create-shipment')
            ->assertStatus(200)
            ->assertSee('selectedServiceTotal', false)
            ->assertSee('selectedServiceNotice', false)
            ->assertSee('data-go-back', false)
            ->assertSee('id="shipmentPackageDescription"', false)
            ->assertSee('<option value="Pickup in Office">Pickup in Office</option>', false)
            ->assertSee('<option value="Home Delivery">Home Delivery</option>', false)
            ->assertDontSee('Door to Door', false)
            ->assertDontSee('Port to Port', false)
            ->assertDontSee('General merchandise', false);
    }

    public function test_layout_exposes_save_consignee_and_gif_overlay(): void
    {
        $this->get('/quote-details')
            ->assertStatus(200)
            ->assertSee('kayProcessOverlay', false)
            ->assertSee('generating-quote.gif', false)
            ->assertSee('processing-shipping.gif', false)
            ->assertSee('app.js?v=', false)
            ->assertSee('kay-paolo.css?v=', false)
            ->assertSee('saveConsignee', false)
            ->assertSee('save-consignee', false);
    }

    public function test_delivery_location_runtime_guard_limits_options(): void
    {
        $script = file_get_contents(public_path('kay-paolo/assets/app.js'));

        $this->assertStringContainsString('initDeliveryLocationSelects', $script);
        $this->assertStringContainsString('initShipmentConfirmationPage', $script);
        $this->assertStringContainsString("route('shipmentConfirmation', '/shipment-confirmation')", $script);
        $this->assertStringContainsString("select.dataset.kayDeliveryLocked = '1'", $script);
        $this->assertStringContainsString('Pickup in Office', $script);
        $this->assertStringContainsString('Home Delivery', $script);
        $this->assertStringNotContainsString('Door to Door', $script);
        $this->assertStringNotContainsString('Port to Port', $script);
    }

    public function test_shipping_proxy_sanitizes_bocicot_payload_for_multiple_packages(): void
    {
        Http::fake([
            '*/api/kay-paolo/update-shipping' => Http::response(['status' => 'success']),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/shipping', [
                'account_number' => '9400',
                'phone_or_account' => '9400',
                'user_id' => 7020,
                'quote_id' => 24743,
                'partner' => 'zion_products',
                'selected_shipper' => 'Regular Air',
                'from_name' => 'Kay Sender',
                'from_email' => 'sender@example.com',
                'from_phone' => '3055551212',
                'from_country' => 'US',
                'from_address' => '1117 NE 163rd St.',
                'from_zip' => '33162',
                'from_city' => 'North Miami Beach',
                'from_state' => 'FL',
                'consignee_id' => 99,
                'to_name' => 'Kay Receiver',
                'to_phone_1' => '5095551212',
                'to_country' => 'HT',
                'to_address' => '10 Rue Test',
                'to_zip' => '6110',
                'to_city' => 'Port-au-Prince',
                'to_state' => 'Ouest',
                'package_count' => 3,
                'dimensions' => [
                    'package_count_ind' => [3],
                    'weight' => [25],
                    'length' => [41],
                    'width' => [12],
                    'height' => [16],
                ],
                'total_value' => 100,
                'delivery_location' => 'Pickup in Office',
                'payment_type' => 'PAID AT AGENT',
            ])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();

            return str_contains($request->url(), '/api/kay-paolo/update-shipping')
                && ! array_key_exists('account_number', $data)
                && ! array_key_exists('phone_or_account', $data)
                && $data['package_count'] === 1
                && $data['partner'] === 'ZION'
                && $data['dimensions']['package_count_ind'] === [3.0]
                && count($data['packages']) === 3
                && $data['selected_shipper'] === 'Regular Air'
                && $data['delivery_option'] === 'Regular Air';
        });
    }

    public function test_shipping_proxy_recovers_from_zion_account_number_schema_error(): void
    {
        Http::fake([
            '*/api/kay-paolo/update-shipping' => Http::sequence()
                ->push([
                    'status' => 'error',
                    'message' => "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'account_number' in 'field list' (SQL: update `shippings` set `account_number` = 9400 where `id` = 24745)",
                ], 500)
                ->push([
                    'status' => 'success',
                    'tracking_number' => 'HTE59174',
                ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/shipping', [
                'user_id' => 7020,
                'quote_id' => 59174,
                'partner' => 'ZION',
                'selected_shipper' => 'Economical Air',
                'from_country' => 'US',
                'from_address' => '1117 NE 163rd St.',
                'from_zip' => '33162',
                'from_city' => 'North Miami Beach',
                'from_state' => 'FL',
                'consignee_id' => 1632,
                'to_name' => 'Judith Sainry Cadet',
                'to_phone_1' => '35853467',
                'to_country' => 'HT',
                'to_address' => '#34, Rue Rosa PAP',
                'to_city' => 'PORT-AU-PRINCE',
                'to_state' => 'OUEST',
                'package_description' => 'Test',
                'dimensions' => [
                    'package_count_ind' => [1, 1],
                    'weight' => [8, 2],
                    'length' => [11, 9],
                    'width' => [11, 6],
                    'height' => [11, 2],
                ],
                'total_value' => 10,
                'delivery_location' => 'Pickup in Office',
                'payment_type' => 'PAID AT AGENT',
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'tracking_number' => 'HTE59174',
            ]);

        Http::assertSentCount(2);
    }

    public function test_account_shows_admin_access_notice_for_admin_session(): void
    {
        $this->withSession([
            'zion.access_token' => 'session-token',
            'zion.user' => [
                'name' => 'Admin User',
                'role_id' => 1,
                'role' => ['name' => 'Admin'],
            ],
        ])
            ->get('/account')
            ->assertStatus(200)
            ->assertSee('Admin access is active', false);
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
