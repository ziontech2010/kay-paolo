<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Mail\ConfirmShipmentMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('data-api-endpoint="http://localhost/zion-api/login"', false)
            ->assertSee('data-api-login', false);

        $this->postJson('/api/kay-paolo/login')->assertStatus(422);
    }

    public function test_api_login_redirects_browser_submits_to_home(): void
    {
        Http::fake([
            '*/api/bocicot/login' => Http::response([
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
            ->assertSee('window.location.replace("http:\/\/localhost")', false)
            ->assertDontSee('/dashboard', false);
    }

    public function test_web_login_api_endpoint_sets_session_for_admin_role(): void
    {
        Http::fake([
            '*/api/bocicot/login' => Http::response([
                'message' => 'Logged in Successfully',
                'message_type' => 'success',
                'error' => 'false',
                'token_type' => 'Bearer',
                'access_token' => 'fake-token',
                'user' => [
                    'id' => 1,
                    'name' => 'Admin User',
                    'role_id' => 1,
                    'role' => ['name' => 'Admin'],
                ],
            ]),
        ]);

        $this->postJson('/zion-api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'role_id' => 1,
        ])
            ->assertOk()
            ->assertSessionHas('zion.access_token', 'fake-token')
            ->assertSessionHas('zion.user.role_id', 1);
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
            ->assertSee('home: "http:\/\/localhost"', false)
            ->assertSee('loginPage: "http:\/\/localhost\/login"', false);
    }

    public function test_shipment_confirmation_page_and_legacy_redirects_render(): void
    {
        $this->get('/shipment-confirmation')
            ->assertStatus(200)
            ->assertSee('data-shipment-confirmation', false)
            ->assertSee('Create Shipment', false)
            ->assertSee('Shipment booked successfully.', false)
            ->assertSee('VIEW LABELS DOCUMENTS AND RECEIPT', false)
            ->assertSee('SHIPMENT NUMBER', false)
            ->assertSee('packageAmountDisplay', false)
            ->assertDontSee('CARRIER DETAILS', false)
            ->assertSee('Open Label', false)
            ->assertSee('Open Receipt', false)
            ->assertSee('Return to Home', false)
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

    public function test_confirm_shipment_email_preview_renders(): void
    {
        $this->get('/emails/confirm-shipment')
            ->assertStatus(200)
            ->assertSee('Shipment booked successfully.', false)
            ->assertSee('View Labels, Documents &amp; Receipt', false)
            ->assertSee('Shipment Number', false)
            ->assertSee('KP-DEMO-1001', false)
            ->assertSee('Open Label', false)
            ->assertSee('Open Receipt', false)
            ->assertSee('Track Shipment', false)
            ->assertSee('Thank You For Your Business', false)
            ->assertSee('kay-paolo/assets/logo/kay-paolo.png', false);
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
            ->assertSee('From: Therlande Louis Jean | Account #9400', false)
            ->assertSee('data-country-select', false)
            ->assertSee('Address 2', false)
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
            ->assertSee('id="shipmentFragile" disabled', false)
            ->assertSee('data-payment-options', false)
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
            ->assertSee('save-consignee', false)
            ->assertSee('countries', false)
            ->assertSee('paymentOptions', false)
            ->assertSee('emailShipment', false)
            ->assertSee('shipmentLabel', false)
            ->assertSee('shipmentReceipt', false);
    }

    public function test_shipment_history_uses_archive_layout_with_dynamic_cards(): void
    {
        $this->get('/shipment-history')
            ->assertStatus(200)
            ->assertSee('Ready to Ship', false)
            ->assertSee('Not Deliverable', false)
            ->assertSee('Voided', false)
            ->assertSee('data-history-status-badge="Available"', false)
            ->assertSee('data-history-category-badge="International"', false)
            ->assertSee('historyCardList', false)
            ->assertSee('history-card-main', false)
            ->assertSee('historyResult', false);

        $this->get('/pickup-list')
            ->assertStatus(200)
            ->assertSee('Pickup List', false)
            ->assertSee('Login first to view pickup list.', false)
            ->assertSee('All Pickups', false)
            ->assertSee('Pending Pickups', false)
            ->assertSee('pickupPendingCount', false);

        $this->get('/shipment-history?view=pickup')
            ->assertRedirect('/pickup-list');

        $script = file_get_contents(public_path('kay-paolo/assets/app.js'));

        $this->assertStringContainsString('history-card-details', $script);
        $this->assertStringContainsString('Detailed Pricing', $script);
        $this->assertStringContainsString('Package Specs', $script);
        $this->assertStringContainsString('Carrier Tracking Details', $script);
        $this->assertStringContainsString('history-more-menu', $script);
        $this->assertStringContainsString('voidHistoryShipment', $script);
        $this->assertStringContainsString('extractHistoryCards', $script);
        $this->assertStringContainsString('updateHistoryBadgesFromRows', $script);
        $this->assertStringContainsString('Object.entries(rawCountries)', $script);
        $this->assertStringContainsString('Object.entries(rawOptions)', $script);
        $this->assertStringContainsString('response?.data?.flat_rates', $script);
        $this->assertStringContainsString('response?.data?.all_options', $script);
        $this->assertStringContainsString('historyDateRangeValue', $script);
        $this->assertStringContainsString("route('pickupList', '/api/kay-paolo/pickup-list')", $script);
        $this->assertStringContainsString('normalizePickupListRows', $script);
        $this->assertStringContainsString('renderPickupListRows', $script);
        $this->assertStringContainsString('pickupListFilterValue', $script);
        $this->assertStringContainsString('per_page: selectedLimit', $script);
        $this->assertStringContainsString('length: selectedLimit', $script);
        $this->assertStringContainsString('created_in: createdIn', $script);
        $this->assertStringContainsString('Complete Pickup', $script);
        $this->assertStringContainsString('client_agent_name', $script);
        $this->assertStringContainsString('direction_url', $script);
        $this->assertStringContainsString('initPickupCompletePage', $script);
        $this->assertStringContainsString("route('pickupListPage', '/pickup-list')", $script);
        $this->assertStringContainsString('pickupListPage', $script);
        $this->assertStringContainsString('zionFlatRateOptions', $script);
        $this->assertStringContainsString("const countryCacheKey = 'kayPaoloCountries:v3'", $script);
        $this->assertStringContainsString('kayPaoloPaymentOptions:v4', $script);
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
        $this->assertStringContainsString('include_in_receipt', $script);
        $this->assertStringContainsString("raw.includes('full integration')", $script);
        $this->assertStringContainsString('isFullIntegrationCard', $script);
        $this->assertStringContainsString('fullIntegrationLogo', $script);
        $this->assertStringContainsString('Full Integration logo', $script);
        $this->assertStringContainsString('filterQuoteCardsForPayload', $script);
        $this->assertStringContainsString('totalPackageWeight(payload) !== 0', $script);
        $this->assertStringContainsString('carrier_logo_url', $script);
        $this->assertStringContainsString('history-more-menu', $script);
        $this->assertStringContainsString("data-history-action=\"label\"", $script);
        $this->assertStringContainsString('applyCouponBtn', $script);
        $this->assertStringContainsString('clearQuoteResults', $script);
        $this->assertStringContainsString("window.localStorage.removeItem('kayPaoloLastQuotePayload')", $script);
        $this->assertStringContainsString("numberValue(firstValue('totalValue', 'package_value'), 0)", $script);
        $this->assertStringContainsString('hasNonZeroMoney(discount)', $script);
        $this->assertStringContainsString('const couponCode = firstValue(\'couponCode\')', $script);
        $this->assertStringContainsString('coupon: couponCode', $script);
        $this->assertStringContainsString('home_delivery_required: isHomeDelivery', $script);
        $this->assertStringContainsString('flat_rate_price: flatRatePrice', $script);
        $this->assertStringContainsString('labelDestination', $script);
        $this->assertStringContainsString('receiptPackageCount', $script);
        $this->assertStringContainsString('renderPackageLabels', $script);
        $this->assertStringContainsString('splitLabelNumbers', $script);
        $this->assertStringContainsString('formatShipmentNumberDisplay', $script);
        $this->assertStringContainsString('dollarText', $script);
        $this->assertStringContainsString('documentServiceSummary', $script);
        $this->assertStringNotContainsString("params.set('access_token', storedToken())", $script);
        $this->assertStringContainsString('isAdminRole', $script);
        $this->assertStringContainsString('enhanceHistoryCards', $script);
        $this->assertStringContainsString('confirmation_email', $script);
        $this->assertStringContainsString('quoteCardValue', $script);
        $this->assertStringNotContainsString('Zion Shipping logo', $script);
        $this->assertStringNotContainsString('Door to Door', $script);
        $this->assertStringNotContainsString('Port to Port', $script);
    }

    public function test_quote_proxy_uses_bocicot_endpoint_before_kay_paolo(): void
    {
        Http::fake([
            '*/web-api/get-quote-result-bocicot' => Http::response([
                'status' => 'success',
                'quotes' => [
                    ['service' => 'Bocicot Regular Boat', 'total' => '25.00'],
                ],
            ]),
            '*/api/kay-paolo/get-quote-result' => Http::response([
                'status' => 'success',
                'quotes' => [
                    ['carrier' => 'Kay Paolo', 'service' => 'Regular Boat', 'grand_total' => '88.00'],
                ],
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/quote', [
                'user_id' => 7020,
                'from_country' => 'US',
                'to_country' => 'HT',
                'coupon_code' => 'SAVE10',
                'delivery_location' => 'Home Delivery',
                'flat_rate' => ['on'],
                'shipment_type' => ['regular_boat_box'],
                'flat_rate_price' => ['88.00'],
            ])
            ->assertOk()
            ->assertJsonPath('quotes.0.service', 'Bocicot Regular Boat')
            ->assertJsonPath('quotes.0.total', '25.00');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/web-api/get-quote-result-bocicot')
                && ($request['promo'] ?? null) === 'SAVE10'
                && ($request['coupon'] ?? null) === 'SAVE10'
                && ($request['promo_code'] ?? null) === 'SAVE10'
                && ($request['home_delivery_required'] ?? null) === 1
                && ($request['total_value'] ?? null) === 0
                && ($request['package_value'] ?? null) === 0
                && ($request['flat_rate_price'][0] ?? null) === '88.00';
        });

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/api/kay-paolo/get-quote-result');
        });
    }

    public function test_countries_and_payment_options_proxy_to_shipping_api(): void
    {
        Http::fake([
            '*/api/bocicot/countries' => Http::response(['message' => 'Not Found'], 404),
            '*/web-api/countries-bocicot' => Http::response(['message' => 'Not Found'], 404),
            '*/api/kay-paolo/countries' => Http::response(['countries' => []]),
            '*/api/countries' => Http::response([
                'countries' => [
                    'HT' => 'Haiti',
                    'US' => 'United States',
                ],
            ]),
            '*/api/bocicot/payment-options*' => Http::response([
                'options' => [
                    ['value' => 'COLLECT', 'label' => 'Collect'],
                ],
            ]),
        ]);

        $this->getJson('/api/kay-paolo/countries')
            ->assertOk()
            ->assertJsonPath('countries.0.code', 'HT')
            ->assertJsonPath('countries.1.code', 'US');

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->getJson('/api/kay-paolo/payment-options?quote_user_id=7020')
            ->assertOk()
            ->assertJsonCount(1, 'options')
            ->assertJsonPath('options.0.value', 'COLLECT');
    }

    public function test_fetch_user_for_quote_prefers_bocicot_web_endpoint(): void
    {
        Http::fake([
            '*/web-api/fetch-user-for-quote-bocicot' => Http::response([
                'status' => 'success',
                'customer' => [
                    'id' => 7020,
                    'account_number' => '9400',
                ],
            ]),
            '*/api/bocicot/fetch-user-for-quote' => Http::response([
                'status' => 200,
                'error' => 'true',
                'message' => 'Your App is Locked!',
                'app_locked' => 'true',
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/fetch-user-for-quote', [
                'phone_or_account' => '9400',
                'customer' => '9400',
            ])
            ->assertOk()
            ->assertJsonPath('customer.account_number', '9400');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/web-api/fetch-user-for-quote-bocicot');
        });

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/api/bocicot/fetch-user-for-quote');
        });
    }

    public function test_fetch_user_for_quote_skips_locked_upstream_response(): void
    {
        Http::fake([
            '*/web-api/fetch-user-for-quote-bocicot' => Http::response([
                'message' => 'Session store not set on request.',
            ], 500),
            '*/api/bocicot/fetch-user-for-quote' => Http::response([
                'status' => 200,
                'error' => 'true',
                'message' => 'Your App is Locked!',
                'app_locked' => 'true',
            ]),
            '*/api/kay-paolo/fetch-user-for-quote' => Http::response([
                'status' => 'success',
                'customer' => [
                    'id' => 7020,
                    'account_number' => '9400',
                ],
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/fetch-user-for-quote', [
                'phone_or_account' => '9400',
                'customer' => '9400',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('customer.account_number', '9400');
    }

    public function test_flat_rates_endpoint_uses_bocicot_api_before_default_option(): void
    {
        Http::fake([
            '*/api/bocicot/flat-rates*' => Http::response([
                'status' => 'success',
                'flat_rates' => [
                    [
                        'code' => 'regular_boat_box',
                        'name' => 'Regular Boat Box',
                        'rate' => '44.50',
                        'dimensions' => [
                            'weight' => '7',
                            'length' => '18',
                            'width' => '12',
                            'height' => '8',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/flat-rates', [
                'user_id' => 7020,
                'from_country' => 'US',
                'to_country' => 'HT',
                'from_state' => 'FL',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('source', 'api')
            ->assertJsonPath('flat_rates.0.code', 'regular_boat_box')
            ->assertJsonPath('flat_rates.0.name', 'Regular Boat Box')
            ->assertJsonPath('flat_rates.0.rate', '44.50')
            ->assertJsonPath('flat_rates.0.dimensions.weight', '7')
            ->assertJsonPath('flat_rates.0.dimensions.length', '18')
            ->assertJsonPath('flat_rates.0.dimensions.width', '12')
            ->assertJsonPath('flat_rates.0.dimensions.height', '8');

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/api/bocicot/flat-rates')
                && str_contains($request->url(), 'user_id=7020')
                && str_contains($request->url(), 'to_country=HT')
                && str_contains($request->url(), 'from_state=FL');
        });
    }

    public function test_flat_rates_endpoint_falls_back_to_default_document_option_when_bocicot_unavailable(): void
    {
        Http::fake([
            '*flat-rates*' => Http::response(['message' => 'Not Found'], 404),
            '*flat-rate*' => Http::response(['message' => 'Not Found'], 404),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/flat-rates', [
                'from_country' => 'US',
                'to_country' => 'HT',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('source', 'fallback')
            ->assertJsonPath('flat_rates.0.slug', 'contains_document')
            ->assertJsonPath('flat_rates.0.label', 'Document')
            ->assertJsonPath('flat_rates.0.default_dimensions.weight', '0.5')
            ->assertJsonPath('flat_rates.0.default_dimensions.length', '12')
            ->assertJsonPath('flat_rates.0.default_dimensions.width', '8')
            ->assertJsonPath('flat_rates.0.default_dimensions.height', '1');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/bocicot/flat-rates');
        });
    }

    public function test_pickup_list_proxy_uses_zion_pickup_list_feed(): void
    {
        Http::fake([
            '*/api/kay-paolo/pickup-list' => Http::response([
                'status' => 'success',
                'pending_count' => 1,
                'count' => 1,
                'pickups' => [
                    [
                        'id' => 501,
                        'pickup_date_display' => 'Sep 11, 2026',
                        'pickup_shipment' => 'Pickup PKP88 / Shipment INV88',
                        'client_agent_name' => 'Kay Client / Kay Agent',
                        'phone' => '7325551212',
                        'full_address' => '414 Main St, Asbury Park, NJ 07712',
                        'package_count' => 2,
                        'weight' => '12.5',
                        'volume' => '1000',
                        'cf' => '0.58',
                        'bill_amount' => '15.00',
                        'status' => 'Pending',
                        'direction_url' => 'https://www.google.com/maps/dir/?api=1&destination=414+Main+St',
                        'complete_url' => '/pickups/501/complete',
                        'complete_path' => '/pickups/501/complete',
                    ],
                ],
            ]),
            '*/api/bocicot/shipping-history-filter' => Http::response([
                'status' => 'success',
                'shippings' => [
                    ['id' => 88, 'status' => 1, 'status_name' => 'Ready to Ship', 'tracking_number' => 'SHOULD-NOT-WIN'],
                ],
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/pickup-list', [
                'limit' => 25,
                'user_id' => 7,
                'account_number' => '9400',
                'created_in' => 'All Pickups',
                'status' => 'pending',
                'pickup_status' => 'pending',
            ])
            ->assertOk()
            ->assertJsonPath('pickups.0.id', 501)
            ->assertJsonPath('pickups.0.client_agent_name', 'Kay Client / Kay Agent')
            ->assertJsonPath('pending_count', 1)
            ->assertJsonCount(1, 'pickups');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/api/kay-paolo/pickup-list')
                && $request->hasHeader('Authorization', 'Bearer fake-token')
                && ($data['limit'] ?? null) === 25
                && ($data['per_page'] ?? null) === 25
                && ($data['length'] ?? null) === 25
                && ($data['page'] ?? null) === 1
                && ($data['start'] ?? null) === 0
                && ($data['filter'] ?? null) === 'all'
                && ($data['created_in'] ?? null) === 'All Pickups'
                && ($data['agent_id'] ?? null) === 7
                && ($data['created_by'] ?? null) === 7
                && ($data['created_by_id'] ?? null) === 7
                && ($data['account_number'] ?? null) === '9400'
                && ! array_key_exists('pickup_status', $data)
                && ! array_key_exists('status', $data);
        });

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/api/bocicot/shipping-history-filter');
        });
    }

    public function test_pickup_complete_page_renders_kay_paolo_completion_ui(): void
    {
        $this->get('/pickups/11521/complete')
            ->assertOk()
            ->assertSee('Complete Pickup', false)
            ->assertSee('data-pickup-id="11521"', false)
            ->assertSee('Take Snapshot', false)
            ->assertSee('Submit Pickup Completion', false)
            ->assertSee('Back To Pickup List', false)
            ->assertSee('pickup-complete-form', false);
    }

    public function test_pickup_show_proxy_forwards_to_zion_pickup_endpoint(): void
    {
        Http::fake([
            '*/api/kay-paolo/pickup/11521' => Http::response([
                'status' => 'success',
                'pickup' => [
                    'id' => 11521,
                    'pickup_shipment' => 'Pickup PK11521 / Shipment INV11521',
                    'client_agent_name' => 'Kay Client / Kay Agent',
                    'phone' => '7325551212',
                    'full_address' => '414 Main St, Asbury Park, NJ 07712',
                    'package_count' => 2,
                    'weight' => '12.5',
                    'volume' => '1000',
                    'cf' => '0.58',
                    'direction_url' => 'https://www.google.com/maps/dir/?api=1&destination=414+Main+St',
                ],
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/pickup/11521')
            ->assertOk()
            ->assertJsonPath('pickup.id', 11521)
            ->assertJsonPath('pickup.client_agent_name', 'Kay Client / Kay Agent');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/kay-paolo/pickup/11521')
                && $request->hasHeader('Authorization', 'Bearer fake-token');
        });
    }

    public function test_pickup_complete_proxy_forwards_multipart_photos(): void
    {
        Http::fake([
            '*/api/kay-paolo/pickup/11521/complete' => Http::response([
                'status' => 'success',
                'message' => 'Pickup completed successfully.',
                'pickup_id' => 11521,
                'redirect' => '/pickup-list',
            ]),
        ]);

        $photo = \Illuminate\Http\UploadedFile::fake()->image('pickup.jpg');

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->post('/api/kay-paolo/pickup/11521/complete', [
                'photos' => [$photo],
                'attachments' => ['snap1.png'],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Pickup completed successfully.')
            ->assertJsonPath('pickup_id', 11521);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/kay-paolo/pickup/11521/complete')
                && $request->hasHeader('Authorization', 'Bearer fake-token')
                && $request->isMultipart();
        });
    }

    public function test_agent_invoices_page_renders_and_proxy_forwards_zion_feed(): void
    {
        $this->get('/agent-invoices')
            ->assertOk()
            ->assertSee('Agent Invoices', false)
            ->assertSee('Login first to view agent invoices.', false)
            ->assertSee('agentInvoicesPage', false)
            ->assertSee('agentInvoicesResult', false);

        Http::fake([
            '*/api/kay-paolo/agent-invoices' => Http::response([
                'status' => 'success',
                'count' => 1,
                'invoices' => [
                    [
                        'id' => 44,
                        'invoice_num' => 'AI-1001',
                        'total_shipments' => 12,
                        'total_agent_commission' => 150.5,
                        'total_card_payments' => 40,
                        'total_due' => 110.5,
                        'download_url' => 'https://www.zionshipping.com/agent-bills/AI-1001.pdf',
                    ],
                ],
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/agent-invoices')
            ->assertOk()
            ->assertJsonPath('invoices.0.invoice_num', 'AI-1001')
            ->assertJsonPath('count', 1);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/kay-paolo/agent-invoices')
                && $request->hasHeader('Authorization', 'Bearer fake-token');
        });

        $script = file_get_contents(public_path('kay-paolo/assets/app.js'));
        $this->assertStringContainsString('initAgentInvoicesPage', $script);
        $this->assertStringContainsString("route('agentInvoices', '/api/kay-paolo/agent-invoices')", $script);
        $this->assertStringContainsString('Total Commission', $script);
        $this->assertStringContainsString('Total Online Payments', $script);
    }

    public function test_pickup_list_proxy_forwards_empty_zion_pickup_list(): void
    {
        Http::fake([
            '*/api/kay-paolo/pickup-list' => Http::response([
                'status' => 'success',
                'error' => false,
                'count' => 0,
                'pending_count' => 0,
                'pickups' => [],
                'message' => 'No active pickups are assigned to your states right now.',
            ]),
            '*/api/bocicot/shipping-history-filter' => Http::response([
                'status' => 'success',
                'shippings' => [
                    ['id' => 99, 'tracking_number' => 'SHOULD-NOT-WIN'],
                ],
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/pickup-list', [
                'limit' => 100,
                'created_in' => 'All Pickups',
            ])
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('pending_count', 0)
            ->assertJsonPath('message', 'No active pickups are assigned to your states right now.')
            ->assertJsonCount(0, 'pickups');

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/api/bocicot/shipping-history-filter');
        });
    }

    public function test_shipment_document_routes_render_kay_branded_document_ui(): void
    {
        Http::fake();

        @unlink(storage_path('app/public/label/label_373988.pdf'));
        @unlink(storage_path('app/public/receipts/receipt_373988.pdf'));

        $this->get('/shipment-label?shipment_id=24755&invoice=373988&id=HTS373988-1%2F2%2C+HTS373988-2%2F2')
            ->assertRedirect('/label/label_373988.pdf');

        $this->assertFileExists(storage_path('app/public/label/label_373988.pdf'));
        $this->assertStringStartsWith('%PDF', (string) file_get_contents(storage_path('app/public/label/label_373988.pdf')));

        $this->get('/label/label_373988.pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get('/shipment-receipt?shipment_id=24755&invoice=373988&id=HTS373988-1%2F2%2C+HTS373988-2%2F2')
            ->assertRedirect('/receipts/receipt_373988.pdf');

        $this->assertFileExists(storage_path('app/public/receipts/receipt_373988.pdf'));
        $this->assertStringStartsWith('%PDF', (string) file_get_contents(storage_path('app/public/receipts/receipt_373988.pdf')));

        $this->get('/receipts/receipt_373988.pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        @unlink(storage_path('app/public/label/label_373988.pdf'));
        @unlink(storage_path('app/public/receipts/receipt_373988.pdf'));
    }

    public function test_public_label_links_do_not_overwrite_existing_pdf_without_context(): void
    {
        Http::fake();

        $invoice = '884411';
        $path = storage_path('app/public/label/label_'.$invoice.'.pdf');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        $original = "%PDF-1.4\nDynamic Consignee Phone 5099876543\n%%EOF";
        file_put_contents($path, $original);

        $this->get('/shipment-label?invoice='.$invoice.'&id=HTB'.$invoice.'-1%2F1')
            ->assertRedirect('/label/label_'.$invoice.'.pdf');

        $this->assertSame($original, (string) file_get_contents($path));

        $this->get('/label/label_'.$invoice.'.pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertSame($original, (string) file_get_contents($path));

        @unlink($path);
    }

    public function test_email_shipment_primes_public_document_pdfs(): void
    {
        Mail::fake();
        $this->configureShipmentConfirmationMailer();

        $invoice = '884422';
        $labelPath = storage_path('app/public/label/label_'.$invoice.'.pdf');
        $receiptPath = storage_path('app/public/receipts/receipt_'.$invoice.'.pdf');
        @unlink($labelPath);
        @unlink($receiptPath);

        $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/kay-paolo/email-shipment', [
                'email' => 'customer@example.com',
                'invoice' => $invoice,
                'tracking_number' => 'HTB'.$invoice.'-1/1',
                'package_count' => 1,
                'service_name' => 'Economical Air',
                'created_at' => '2026-08-13 09:00:00',
                'shipper_name' => 'Dynamic Shipper',
                'shipper_address' => '100 Main St',
                'shipper_contact' => '3055551212 / dynamic-shipper@example.com',
                'consignee_name' => 'Dynamic Consignee',
                'consignee_address' => '12 Rue Test',
                'consignee_contact' => '5095551212',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('mailer', 'zeptomail');

        Mail::assertSent(ConfirmShipmentMail::class);
        $this->assertFileExists($labelPath);
        $this->assertFileExists($receiptPath);
        $this->assertStringStartsWith('%PDF', (string) file_get_contents($labelPath));
        $this->assertStringStartsWith('%PDF', (string) file_get_contents($receiptPath));

        @unlink($labelPath);
        @unlink($receiptPath);
    }

    public function test_email_shipment_reports_missing_delivery_mailer(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'log',
            'services.zeptomail.token' => null,
        ]);

        $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/kay-paolo/email-shipment', [
                'email' => 'customer@example.com',
                'invoice' => '884423',
                'tracking_number' => 'HTB884423-1/1',
            ])
            ->assertStatus(502)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Shipment confirmation email is not configured for delivery.');

        Mail::assertNothingSent();
    }

    public function test_zeptomail_transport_posts_confirmation_email_to_provider(): void
    {
        config([
            'mail.from.address' => 'info@kaypaoloshipping.com',
            'mail.from.name' => 'Kay Paolo Shipping',
            'services.zeptomail.host' => 'api.zeptomail.com',
            'services.zeptomail.token' => 'test-token',
        ]);

        Http::fake([
            'https://api.zeptomail.com/v1.1/email' => Http::response(['request_id' => 'req-123']),
        ]);

        Mail::mailer('zeptomail')->to('customer@example.com')->send(new ConfirmShipmentMail([
            'shipmentNumber' => 'HTB101187-1/5',
            'trackingNumber' => 'HTB101187-1/5',
            'recipientName' => 'Therlande Louis Jean',
        ]));

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();

            return $request->url() === 'https://api.zeptomail.com/v1.1/email'
                && ($data['from']['address'] ?? null) === 'info@kaypaoloshipping.com'
                && ($data['from']['name'] ?? null) === 'Kay Paolo Shipping'
                && ($data['to'][0]['email_address']['address'] ?? null) === 'customer@example.com'
                && str_contains((string) ($data['subject'] ?? ''), 'HTB101187-1/5')
                && str_contains((string) ($data['htmlbody'] ?? ''), 'Therlande Louis Jean');
        });
    }

    public function test_receipt_pdf_includes_package_details_from_shipment_history(): void
    {
        $historyRow = [
            'id' => 24755,
            'invoice_num' => '479029',
            'tracking_number' => 'HTS479029-1/1',
            'package_description' => 'Household Goods',
            'package_count' => 2,
            'packages' => [],
            'dimensions' => [
                'package_count_ind' => [2],
                'weight' => [45],
                'length' => [24],
                'width' => [18],
                'height' => [16],
            ],
            'shipper_name' => 'Kay Shipper',
            'consignee_name' => 'Kay Consignee',
            'shipper' => [
                'phone' => '3051234567',
                'contact' => '3051234567 / history-shipper@example.com',
            ],
            'consignee' => [
                'phone' => '5099876543',
            ],
            'data' => [
                'delivery_date' => '2026-08-24 11:59:00',
            ],
            'selected_shipper' => 'Economical Air',
            'customer_account_number' => 'ACCT-479029',
            'freight' => 80,
            'tax' => 5,
            'total' => 85,
            'created_at' => '2026-08-01 10:00:00',
        ];

        Http::fake([
            '*/api/bocicot/shipping-history-filter' => Http::response([
                'status' => 'success',
                'shippings' => [$historyRow],
            ]),
        ]);

        @unlink(storage_path('app/public/receipts/receipt_479029.pdf'));
        @unlink(storage_path('app/public/label/label_479029.pdf'));

        $this->withSession(['zion.access_token' => 'test-token'])
            ->get('/shipment-receipt?invoice=479029&id=HTS479029-1%2F1')
            ->assertRedirect('/receipts/receipt_479029.pdf');

        $path = storage_path('app/public/receipts/receipt_479029.pdf');
        $this->assertFileExists($path);
        $pdf = (string) file_get_contents($path);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertTrue(
            str_contains($pdf, 'Household') || str_contains($pdf, 'Goods') || str_contains($pdf, '45'),
            'Receipt PDF should embed package description or weight from history.'
        );

        $service = app(\App\Services\ShipmentDocumentPdfService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('documentPayload');
        $method->setAccessible(true);
        $documentPayload = $method->invoke($service, [
            'invoice' => '479029',
            'id' => 'HTS479029-1/1',
        ], [
            'response' => $historyRow,
            'payload' => [],
            'selected' => [],
        ]);

        $this->assertSame('history-shipper@example.com', $documentPayload['shipperEmail']);
        $this->assertSame('3051234567', $documentPayload['shipperPhone']);
        $this->assertSame('5099876543', $documentPayload['consigneePhone']);
        $this->assertSame('ACCT-479029', $documentPayload['accountNumber']);
        $this->assertSame('Aug 24, 2026', $documentPayload['deliveryDate']);
        $this->assertSame('AUG 24', $documentPayload['labelDeliveryDate']);

        $this->withSession(['zion.access_token' => 'test-token'])
            ->get('/shipment-label?invoice=479029&id=HTS479029-1%2F1')
            ->assertRedirect('/label/label_479029.pdf');

        $labelPath = storage_path('app/public/label/label_479029.pdf');
        $this->assertFileExists($labelPath);
        $labelPdf = (string) file_get_contents($labelPath);
        $this->assertStringStartsWith('%PDF', $labelPdf);
        $labelHtml = view('documents.pdf.label', $documentPayload)->render();
        $this->assertStringContainsString('AUG 24', $labelHtml);
        $this->assertStringNotContainsString('Aug 24, 2026', $labelHtml);
        $this->assertStringNotContainsString('11:59', $labelHtml);
        $this->assertStringNotContainsString('Delivery Date:', $labelHtml);
        $this->assertStringNotContainsString($documentPayload['barcodeValue'].' | ', $labelHtml);
        $this->assertStringNotContainsString('Invoice 479029', $labelHtml);
        $this->assertStringNotContainsString('Delivery DLV479029', $labelHtml);
        $receiptTemplates = file_get_contents(resource_path('views/documents/pdf/receipt.blade.php'))
            .file_get_contents(resource_path('views/documents/receipt.blade.php'));
        $this->assertStringContainsString('Package Description', $receiptTemplates);
        $this->assertStringNotContainsString('Task Description', $receiptTemplates);

        @unlink($path);
        @unlink($labelPath);
    }

    public function test_quote_proxy_falls_back_when_api_endpoint_requires_session_store(): void
    {
        Http::fake([
            '*/web-api/get-quote-result-bocicot' => Http::response([
                'status' => 'success',
                'quotes' => [
                    ['carrier' => 'ZION', 'service' => 'Economical Air', 'total' => '25.00'],
                ],
            ]),
            '*/api/kay-paolo/get-quote-result' => Http::response([
                'message' => 'Session store not set on request.',
            ], 500),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/quote', [
                'user_id' => 7020,
                'from_country' => 'US',
                'to_country' => 'HT',
                'coupon_code' => 'SAVE10',
                'delivery_location' => 'Home Delivery',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('quotes.0.service', 'Economical Air');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/web-api/get-quote-result-bocicot')
                && ($request['promo'] ?? null) === 'SAVE10'
                && ($request['coupon_code'] ?? null) === 'SAVE10'
                && ($request['delivery_location'] ?? null) === 'Home Delivery';
        });
    }

    public function test_admin_page_is_role_gated_and_updates_content(): void
    {
        Storage::fake('local');

        $this->get('/admin')->assertForbidden();

        $session = [
            'zion.access_token' => 'session-token',
            'zion.user' => [
                'name' => 'Admin User',
                'role_id' => 1,
                'role' => ['name' => 'Admin'],
            ],
        ];

        $this->withSession($session)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Who We Are Pictures', false);

        $this->withSession([
            'zion.access_token' => 'session-token',
            'zion.user' => [
                'name' => 'Basic Admin User',
                'role_id' => 12,
                'role' => ['name' => 'Basic Admin'],
            ],
        ])
            ->get('/admin')
            ->assertOk();

        $this->withSession($session)
            ->post('/admin', [
                'meta_description' => 'Updated Kay Paolo logistics description.',
                'who_headline' => 'Updated Who Headline',
                'who_body' => 'Updated body copy for Kay Paolo content.',
                'process_step_1_title' => 'Updated Step One',
                'process_step_1_body' => 'Updated step copy.',
            ])
            ->assertRedirect('/admin');

        Storage::disk('local')->assertExists('kay-paolo/content.json');
    }

    public function test_shipping_proxy_sanitizes_bocicot_payload_for_multiple_packages(): void
    {
        Mail::fake();
        $this->configureShipmentConfirmationMailer();

        Http::fake([
            '*/web-api/update-shipping-bocicot' => Http::response(['status' => 'success']),
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
                'include_in_receipt' => 1,
            ])
            ->assertOk()
            ->assertJson(['status' => 'success'])
            ->assertJsonPath('confirmation_email.mailer', 'zeptomail');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();

            return str_contains($request->url(), '/web-api/update-shipping-bocicot')
                && ! array_key_exists('account_number', $data)
                && ! array_key_exists('phone_or_account', $data)
                && $data['package_count'] === 3
                && $data['partner'] === 'ZION'
                && $data['dimensions']['package_count_ind'] === [3.0]
                && count($data['packages']) === 3
                && $data['include_in_receipt'] === 1
                && $data['selected_shipper'] === 'Regular Air'
                && $data['delivery_option'] === 'Regular Air';
        });

        Mail::assertSent(ConfirmShipmentMail::class, function ($mail) {
            return ($mail->shipment['recipientName'] ?? null) === 'Kay Sender'
                && ($mail->shipment['shipperContact'] ?? null) === '3055551212 / sender@example.com';
        });
    }

    public function test_shipping_creation_sends_confirmation_to_customer_email_alias(): void
    {
        Mail::fake();
        $this->configureShipmentConfirmationMailer();

        Http::fake([
            '*/web-api/update-shipping-bocicot' => Http::response([
                'status' => 'success',
                'tracking_number' => 'HTCUST123',
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/shipping', [
                'user_id' => 7020,
                'quote_id' => 24744,
                'partner' => 'zion_products',
                'selected_shipper' => 'Regular Air',
                'from_name' => 'Kay Customer',
                'customer_email' => 'customer@example.com',
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
                'package_count' => 1,
                'dimensions' => [
                    'package_count_ind' => [1],
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
            ->assertJsonPath('confirmation_email.status', 'success')
            ->assertJsonPath('confirmation_email.email', 'customer@example.com')
            ->assertJsonPath('confirmation_email.mailer', 'zeptomail');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return str_contains($request->url(), '/web-api/update-shipping-bocicot')
                && ! array_key_exists('customer_email', $request->data());
        });

        Mail::assertSent(ConfirmShipmentMail::class, function ($mail) {
            $sentToCustomer = method_exists($mail, 'hasTo')
                ? $mail->hasTo('customer@example.com')
                : collect($mail->to)->contains(fn ($recipient) => ($recipient['address'] ?? null) === 'customer@example.com');

            return $sentToCustomer
                && ($mail->shipment['recipientName'] ?? null) === 'Kay Customer'
                && ($mail->shipment['shipperContact'] ?? null) === '3055551212 / customer@example.com';
        });
    }

    public function test_shipping_creation_sends_kay_paolo_email_when_create_response_includes_html(): void
    {
        Mail::fake();
        $this->configureShipmentConfirmationMailer();

        Http::fake([
            '*/web-api/update-shipping-bocicot' => Http::response([
                'html' => '<div class="form-head"><h3>VIEW LABELS DOCUMENTS AND RECEIPT</h3></div><a href="/receipt/receipt_253271.pdf">View Receipt</a>',
                'tracking_number' => 'HTS253271-1/1',
                'invoice_num' => '253271',
            ]),
        ]);

        $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/shipping', [
                'user_id' => 7020,
                'quote_id' => 93602,
                'partner' => 'zion_products',
                'selected_shipper' => 'Economical Air',
                'from_name' => 'Therlande Louis Jean',
                'from_email' => 'ztionline@gmail.com',
                'from_phone' => '7867027700',
                'from_country' => 'US',
                'from_address' => '1117 NE 163rd St',
                'from_zip' => '33162',
                'from_city' => 'North Miami Beach',
                'from_state' => 'FL',
                'consignee_id' => 40285,
                'to_name' => 'test 6',
                'to_phone_1' => '34215356',
                'to_country' => 'HT',
                'to_address' => 'San Juan de la Maguana San Juan',
                'to_city' => 'Port-au-Prince',
                'to_state' => 'Ouest',
                'package_count' => 1,
                'dimensions' => [
                    'package_count_ind' => [1],
                    'weight' => [8],
                    'length' => [11],
                    'width' => [11],
                    'height' => [11],
                ],
                'total_value' => 0,
                'delivery_location' => 'Pickup in Office',
                'payment_type' => 'PAID AT AGENT',
            ])
            ->assertOk()
            ->assertJsonMissingPath('html')
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('confirmation_email.status', 'success')
            ->assertJsonPath('confirmation_email.email', 'ztionline@gmail.com');

        Mail::assertSent(ConfirmShipmentMail::class, function ($mail) {
            return ($mail->shipment['shipmentNumber'] ?? null) === 'HTS253271-1/1'
                && ($mail->shipment['recipientName'] ?? null) === 'Therlande Louis Jean';
        });
    }

    public function test_create_shipment_document_context_keeps_delivery_date_and_account_number(): void
    {
        Mail::fake();
        $this->configureShipmentConfirmationMailer();

        $invoice = '884433';
        @unlink(storage_path('app/public/receipts/receipt_'.$invoice.'.pdf'));
        @unlink(storage_path('app/public/label/label_'.$invoice.'.pdf'));
        Cache::flush();

        Http::fake([
            '*/web-api/update-shipping-bocicot' => Http::response([
                'status' => 'success',
                'invoice_num' => $invoice,
                'tracking_number' => 'HTB'.$invoice.'-1/1',
            ]),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer fake-token')
            ->postJson('/api/kay-paolo/shipping', [
                'account_number' => '9400',
                'from_account' => '9400',
                'phone_or_account' => '9400',
                'user_id' => 7020,
                'quote_id' => 24745,
                'partner' => 'zion_products',
                'selected_shipper' => 'Regular Air',
                'deliveryEstimateDate' => '2026-10-22 00:00:00',
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
                'package_count' => 1,
                'dimensions' => [
                    'package_count_ind' => [1],
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
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['document_context_key'])
            ->assertJsonPath('confirmation_email.mailer', 'zeptomail');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();

            return str_contains($request->url(), '/web-api/update-shipping-bocicot')
                && ! array_key_exists('account_number', $data)
                && ! array_key_exists('from_account', $data)
                && ! array_key_exists('phone_or_account', $data)
                && ($data['deliveryEstimateDate'] ?? null) === '2026-10-22 00:00:00';
        });

        $contextKey = $response->json('document_context_key');
        $this->assertNotEmpty($contextKey);

        $shipment = Cache::get('kay_paolo:shipment_context:'.$contextKey);
        $this->assertSame('9400', $shipment['payload']['account_number'] ?? null);
        $this->assertSame('2026-10-22 00:00:00', $shipment['payload']['deliveryEstimateDate'] ?? null);

        $service = app(\App\Services\ShipmentDocumentPdfService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('documentPayload');
        $method->setAccessible(true);
        $documentPayload = $method->invoke($service, [
            'invoice' => $invoice,
            'id' => 'HTB'.$invoice.'-1/1',
        ], $shipment);

        $this->assertSame('9400', $documentPayload['accountNumber']);
        $this->assertSame('Oct 22, 2026', $documentPayload['deliveryDate']);
        $this->assertSame('OCT 22', $documentPayload['labelDeliveryDate']);

        $labelHtml = view('documents.pdf.label', $documentPayload)->render();
        $this->assertStringContainsString('OCT 22', $labelHtml);
        $this->assertStringNotContainsString('Oct 22, 2026', $labelHtml);
        $this->assertStringNotContainsString('12:00', $labelHtml);
        $this->assertStringNotContainsString('Delivery Date:', $labelHtml);
        $this->assertStringNotContainsString($documentPayload['barcodeValue'].' | ', $labelHtml);

        @unlink(storage_path('app/public/receipts/receipt_'.$invoice.'.pdf'));
        @unlink(storage_path('app/public/label/label_'.$invoice.'.pdf'));
    }

    public function test_shipping_proxy_recovers_from_zion_account_number_schema_error(): void
    {
        Http::fake([
            '*/web-api/update-shipping-bocicot' => Http::sequence()
                ->push([
                    'message' => 'Session store not set on request.',
                ], 500)
                ->push([
                    'status' => 'success',
                    'tracking_number' => 'HTE59174',
                ]),
            '*/api/bocicot/update-shipping' => Http::response([
                'message' => 'Not Found',
            ], 404),
            '*/api/kay-paolo/update-shipping' => Http::response([
                'status' => 'error',
                'message' => "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'account_number' in 'field list' (SQL: update `shippings` set `account_number` = 9400 where `id` = 24745)",
            ], 500),
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

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return str_contains($request->url(), '/web-api/update-shipping-bocicot');
        });
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

    private function configureShipmentConfirmationMailer(): void
    {
        config(['services.zeptomail.token' => 'test-zeptomail-token']);
    }
}
