@extends('layouts.site')

@section('title', 'Get A Quote | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Create A Shipment</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Get A Quote</span></div>
    </div>
</div>
@endsection

@section('content')
<section>
    <div class="wrap quote-page-grid">
        <form class="contact-form" id="quoteForm">
            <h3 style="font-size:20px;margin-bottom:6px;">Shipment Details</h3>
            <p class="muted-text">Live quotes are forwarded to dev Zion Shipping using Kay Paolo API routes.</p>

            @unless (session('zion.access_token'))
                <div class="api-alert error">Login first to generate authenticated Zion quotes.</div>
            @endunless

            <div class="form-row">
                <div class="field"><label for="customerLookup">Customer Phone / Account</label><input type="text" id="customerLookup" placeholder="Optional for agents"></div>
                <div class="field field-with-button"><label>&nbsp;</label><button type="button" class="btn btn-navy" id="fetchCustomerBtn">Find Customer</button></div>
            </div>
            <input type="hidden" id="quoteUserId" name="user_id">
            <div id="customerLookupResult" class="api-inline-result"></div>

            <div class="form-row">
                <div class="field"><label for="from_country">Origin Country</label>
                    <select id="from_country" name="from_country" data-country-select>
                        <option value="US">United States</option>
                        <option value="HT">Haiti</option>
                        <option value="DO">Dominican Republic</option>
                        <option value="CA">Canada</option>
                    </select>
                </div>
                <div class="field"><label for="to_country">Destination Country</label>
                    <select id="to_country" name="to_country" data-country-select>
                        <option value="HT">Haiti</option>
                        <option value="DO">Dominican Republic</option>
                        <option value="US">United States</option>
                        <option value="CA">Canada</option>
                        <option value="JM">Jamaica</option>
                        <option value="TT">Trinidad and Tobago</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="field"><label for="from_address">Origin Address</label><input type="text" id="from_address" value="{{ session('zion.user.shipper_address', '1117 NE 163rd St.') }}"></div>
                <div class="field"><label for="to_address">Destination Address</label><input type="text" id="to_address" required placeholder="Street address"></div>
            </div>
            <div class="form-row">
                <div class="field"><label for="from_city">Origin City</label><input type="text" id="from_city" value="{{ session('zion.user.shipper_city', 'North Miami Beach') }}"></div>
                <div class="field"><label for="to_city">Destination City</label><input type="text" id="to_city" required placeholder="City"></div>
            </div>
            <div class="form-row">
                <div class="field"><label for="from_state">Origin State</label><input type="text" id="from_state" value="{{ session('zion.user.shipper_state', 'FL') }}"></div>
                <div class="field"><label for="to_state">Destination State</label><input type="text" id="to_state" placeholder="State"></div>
            </div>
            <div class="form-row">
                <div class="field"><label for="from_zip">Origin Zip</label><input type="text" id="from_zip" value="{{ session('zion.user.shipper_zip', '33162') }}"></div>
                <div class="field"><label for="to_zip">Destination Zip</label><input type="text" id="to_zip" placeholder="Postal code"></div>
            </div>

            <div class="form-row">
                <div class="field"><label for="to_name">Consignee Name</label><input type="text" id="to_name" required></div>
                <div class="field"><label for="to_phone_1">Consignee Phone</label><input type="tel" id="to_phone_1" required></div>
            </div>
            <div class="form-row">
                <div class="field"><label for="consignee_id">Existing Consignee ID</label><input type="number" id="consignee_id" placeholder="Required for shipment creation"></div>
                <div class="field"><label for="delivery_location">Delivery Location</label><select id="delivery_location"><option>Home Delivery</option><option>Office Pickup</option></select></div>
            </div>

            <div class="form-row">
                <div class="field"><label for="package_weight">Weight (LBS)</label><input type="number" id="package_weight" min="0.1" step="0.1" value="5" required></div>
                <div class="field"><label for="package_value">Declared Value (USD)</label><input type="number" id="package_value" min="1" step="0.01" value="10" required></div>
            </div>
            <div class="form-row">
                <div class="field"><label for="package_length">Length</label><input type="number" id="package_length" min="1" value="12" required></div>
                <div class="field"><label for="package_width">Width</label><input type="number" id="package_width" min="1" value="8" required></div>
            </div>
            <div class="form-row">
                <div class="field"><label for="package_height">Height</label><input type="number" id="package_height" min="1" value="6" required></div>
                <div class="field"><label for="shipment_type">Package Type</label><select id="shipment_type"><option value="">Regular Package</option><option value="contains_document">Document</option></select></div>
            </div>
            <div class="field"><label for="package_description">Package Description</label><textarea id="package_description" placeholder="Documents, clothing, electronics, etc.">General merchandise</textarea></div>

            <button type="submit" class="btn btn-gold btn-block">Generate Quote</button>
        </form>

        <div class="quote-side">
            <div class="eyebrow" style="color:var(--gold-300);">Live Zion API</div>
            <h3>Quote and ship without changing Zion</h3>
            <ul>
                <li><span>01</span> Kay Paolo sends the request to its local proxy route.</li>
                <li><span>02</span> The proxy adds the Zion token and calls dev Zion.</li>
                <li><span>03</span> Zion returns carrier cards, quote IDs, and shipment documents.</li>
            </ul>
            <a href="{{ route('tracking') }}" class="btn btn-outline btn-block" style="margin-top:26px;">Track A Shipment</a>
        </div>
    </div>

    <div class="wrap api-results-wrap">
        <div class="api-loader" id="quoteLoader" hidden>
            <img src="{{ asset('kay-paolo/assets/generating-quote.gif') }}" alt="Generating quote">
        </div>
        <div id="quoteResult" class="api-result-grid"></div>
        <div class="api-loader" id="shippingLoader" hidden>
            <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Processing shipping">
        </div>
        <div id="shippingResult"></div>
    </div>
</section>
@endsection
