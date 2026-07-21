@extends('layouts.site')

@php
    $user = session('zion.user', []);
    $shipperName = $user['name'] ?? 'Therlande Louis Jean';
    $shipperEmail = $user['email'] ?? 'zlionline@gmail.com';
    $shipperPhone = $user['phone'] ?? $user['mobile'] ?? '7867027700';
    $shipperAccount = $user['account_number'] ?? $user['id'] ?? '9400';
    $shipperAddress = $user['shipper_address'] ?? $user['address'] ?? '1117 NE 163rd St.';
    $shipperApt = $user['shipper_apt'] ?? $user['apt'] ?? '';
    $shipperCity = $user['shipper_city'] ?? $user['city'] ?? 'North Miami Beach';
    $shipperState = $user['shipper_state'] ?? $user['state'] ?? 'FL';
    $shipperZip = $user['shipper_zip'] ?? $user['zip'] ?? '33162';
@endphp

@section('title', 'Quote Details | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Get A Quote</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Get A Quote</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap quote-page-grid">
        <form class="contact-form" id="quoteForm" style="padding: 0; background: none; border: none; box-shadow: none; grid-column: 1 / -1">
            <input type="hidden" id="quoteUserId" name="user_id" value="{{ request('customer') }}">
            <input type="hidden" id="consignee_id" name="consignee_id" value="">
            <input type="hidden" id="shipment_type" name="shipment_type" value="">

            <div class="api-alert error" id="authNotice">Login first to generate authenticated Zion quotes.</div>

            <div class="create-shipment-grid">
                <div class="shipment-card" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0">
                    <div class="shipment-card-header" id="fromCardTitle" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                        From: {{ $shipperName }}
                    </div>
                    <div class="shipment-card-body" style="flex-grow: 1">
                        <div class="field">
                            <label>Name</label>
                            <input type="text" id="from_name" value="{{ $shipperName }}" readonly style="background: #f8fafc; font-weight: 600; color: #334155">
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label>Email</label>
                                <input type="email" id="from_email" value="{{ $shipperEmail }}" readonly style="background: #f8fafc; font-weight: 600; color: #334155">
                            </div>
                            <div class="field">
                                <label>Phone</label>
                                <input type="text" id="from_phone" value="{{ $shipperPhone }}" readonly style="background: #f8fafc; font-weight: 600; color: #334155">
                            </div>
                        </div>
                        <div class="form-row-3">
                            <div class="field" style="grid-column: span 2">
                                <label for="from_country">Country *</label>
                                <select id="from_country" name="from_country" required>
                                    <option value="US" selected>United States</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>Account Number</label>
                                <input type="text" id="from_account" value="{{ $shipperAccount }}" readonly style="background: #f8fafc; font-weight: 600; color: #334155">
                            </div>
                        </div>
                        <div class="form-row-3">
                            <div class="field">
                                <label for="from_zip">Zip Code</label>
                                <input type="text" id="from_zip" value="{{ $shipperZip }}" required>
                            </div>
                            <div class="field">
                                <label for="from_city">City *</label>
                                <input type="text" id="from_city" value="{{ $shipperCity }}" required>
                            </div>
                            <div class="field">
                                <label for="from_state">State *</label>
                                <input type="text" id="from_state" value="{{ $shipperState }}" required>
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label for="from_address">Address *</label>
                                <input type="text" id="from_address" value="{{ $shipperAddress }}" required>
                            </div>
                            <div class="field">
                                <label>Apt/Ste/Unit</label>
                                <input type="text" id="from_apt" value="{{ $shipperApt }}" placeholder="None specified">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="shipment-card" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0">
                    <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                        To:
                    </div>
                    <div class="shipment-card-body" style="flex-grow: 1">
                        <div class="consignee-toggle" style="display: flex; gap: 24px; margin-bottom: 24px; border-bottom: 1px solid var(--line); padding-bottom: 14px">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: var(--navy-900); cursor: pointer">
                                <input type="radio" name="consigneeType" value="new" checked style="accent-color: var(--gold-500); width: 18px; height: 18px">
                                New Consignee
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: var(--ink-400); cursor: pointer">
                                <input type="radio" name="consigneeType" value="existing" style="accent-color: var(--gold-500); width: 18px; height: 18px">
                                Existing Consignee
                            </label>
                        </div>

                        <div class="field" id="existingConsigneeSelectField" style="display: none; margin-bottom: 20px">
                            <label for="existingConsignee">Select Consignee</label>
                            <select id="existingConsignee">
                                <option value="">-- Select Existing Consignee --</option>
                            </select>
                            <div id="existingConsigneeResult" class="api-inline-result"></div>
                        </div>

                        <div class="field">
                            <label for="toName">Consignee Name</label>
                            <input type="text" id="toName" placeholder="Consignee Name" required>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label for="toPhone">Phone</label>
                                <input type="text" id="toPhone" placeholder="Phone" required>
                            </div>
                            <div class="field">
                                <label for="toHomePhone">Home Phone</label>
                                <input type="text" id="toHomePhone" placeholder="Home Phone">
                            </div>
                        </div>
                        <div class="form-row-3">
                            <div class="field" style="grid-column: span 2">
                                <label for="toCountry">Country *</label>
                                <select id="toCountry" required>
                                    <option value="">Select Country</option>
                                    <option>Haiti</option>
                                    <option>Dominican Republic</option>
                                    <option>United States</option>
                                    <option>Canada</option>
                                    <option>Jamaica</option>
                                    <option>Trinidad and Tobago</option>
                                    <option>United Kingdom</option>
                                    <option>Germany</option>
                                    <option>France</option>
                                    <option>Nigeria</option>
                                    <option>Japan</option>
                                    <option>Australia</option>
                                    <option>Brazil</option>
                                    <option>Peru</option>
                                    <option>India</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="toZip">Zip Code</label>
                                <input type="text" id="toZip" placeholder="Zip Code">
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label for="toAddress">Address *</label>
                                <input type="text" id="toAddress" placeholder="Enter a location" required>
                            </div>
                            <div class="field">
                                <label for="toApt">Apt/Ste/Unit</label>
                                <input type="text" id="toApt" placeholder="Apt/Ste/Unit">
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label for="toCity">City *</label>
                                <input type="text" id="toCity" placeholder="City" required>
                            </div>
                            <div class="field">
                                <label for="toState">State *</label>
                                <input type="text" id="toState" placeholder="State" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="shipment-card" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0">
                    <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                        Package Information
                    </div>
                    <div class="shipment-card-body" style="flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between">
                        <div>
                            <div id="packagesContainer">
                                <div class="package-block" id="packageBlock1" style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--line)">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px">
                                        <h4 class="package-title" style="margin: 0; border: none; padding: 0; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 700; color: var(--navy-900)">Package 1</h4>
                                        <button type="button" class="remove-package-btn btn" style="padding: 4px 12px; font-size: 12px; border: 1px solid #fca5a5; color: #ef4444; background: #fff5f5; border-radius: 6px; display: none">Remove</button>
                                    </div>

                                    <div class="form-row-2" style="margin-bottom: 14px">
                                        <div class="checkbox-field" style="margin-top: 0; align-self: center">
                                            <input type="hidden" value="0" name="flat_rate[]" class="pkg-flat-rate-hidden">
                                            <input type="checkbox" id="pkgFlatRate1" name="flat_rate[]" class="pkg-flat-rate" value="on">
                                            <label for="pkgFlatRate1">Flat Rate?</label>
                                        </div>
                                        <div class="field" style="margin-bottom: 0">
                                            <label for="pkgCount1">Pkg Count</label>
                                            <select id="pkgCount1" class="pkg-count">
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="field pkg-flat-rate-field" style="display: none; margin-bottom: 14px">
                                        <label for="pkgFlatRateType1">Flat Rate Item</label>
                                        <input type="hidden" value="" name="shipment_type[]" class="pkg-flat-rate-type-hidden">
                                        <select id="pkgFlatRateType1" name="shipment_type[]" class="pkg-flat-rate-type">
                                            <option value="">-- Select Flat Rate Item --</option>
                                        </select>
                                        <div class="api-inline-result pkg-flat-rate-note"></div>
                                    </div>

                                    <div style="display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 12px; align-items: end">
                                        <div class="field" style="margin-bottom: 0">
                                            <label>Weight *</label>
                                            <input type="text" class="pkg-weight" placeholder="Weight In lbs" required>
                                        </div>
                                        <div class="field" style="margin-bottom: 0">
                                            <label>Length *</label>
                                            <input type="text" class="pkg-length" placeholder="Length" required>
                                        </div>
                                        <div class="field" style="margin-bottom: 0">
                                            <label>Width *</label>
                                            <input type="text" class="pkg-width" placeholder="Width" required>
                                        </div>
                                        <div class="field" style="margin-bottom: 0">
                                            <label>Height *</label>
                                            <input type="text" class="pkg-height" placeholder="Height" required>
                                        </div>
                                        <button type="button" class="add-package-btn btn btn-navy" style="padding: 10px; height: 45px; width: 45px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; border-radius: 8px; margin-bottom: 0">+</button>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label for="totalValue">Total Value *</label>
                                <input type="text" id="totalValue" placeholder="Enter a Total Shipment value" required>
                            </div>
                            <div class="field">
                                <label for="packageDescription">Package Description</label>
                                <textarea id="packageDescription" placeholder="Documents, clothing, electronics, etc.">General merchandise</textarea>
                            </div>
                        </div>
                        <div class="checkbox-field" style="margin-top: 16px">
                            <input type="checkbox" id="fragileShipment">
                            <label for="fragileShipment">Fragile shipment</label>
                        </div>
                    </div>
                </div>

                <div class="shipment-card" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0">
                    <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                        Additional Details
                    </div>
                    <div class="shipment-card-body" style="flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between">
                        <div>
                            <div class="field">
                                <label for="deliveryLocation">Delivery location *</label>
                                <select id="deliveryLocation" required>
                                    <option value="">-- Select Delivery Location --</option>
                                    <option>Door to Door</option>
                                    <option>Port to Port</option>
                                    <option>Door to Port</option>
                                    <option>Port to Door</option>
                                    <option>Home Delivery</option>
                                    <option>Office Pickup</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="couponCode">Coupon/Promo Code</label>
                                <div style="display: flex; gap: 10px">
                                    <input type="text" id="couponCode" placeholder="Enter code" style="flex-grow: 1; margin-bottom: 0">
                                    <button type="button" class="btn btn-navy" style="padding: 0 20px; font-size: 13px; border-radius: 8px">Apply</button>
                                </div>
                            </div>
                            <div class="field">
                                <label for="extraServiceCharge">Extra Service Charge</label>
                                <input type="text" id="extraServiceCharge" placeholder="Extra Service Charge">
                            </div>
                        </div>
                        <div>
                            <div class="checkbox-field" style="margin-bottom: 24px; margin-top: 16px">
                                <input type="checkbox" id="includeReceipt">
                                <label for="includeReceipt">Include in receipt</label>
                            </div>

                            <button type="submit" class="btn btn-gold btn-block" style="font-size: 16px; font-weight: 700; padding: 14px 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 8px">
                                <span style="font-size: 18px; font-weight: 700; line-height: 1">+</span> Got Quote
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="api-loader" id="quoteLoader" hidden>
            <img src="{{ asset('kay-paolo/assets/generating-quote.gif') }}" alt="Generating quote">
        </div>
        <div class="quote-results-container" id="quoteResult"></div>

        <div class="api-loader" id="shippingLoader" hidden>
            <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Processing shipping">
        </div>
        <div id="shippingResult"></div>
    </div>
</section>

<div class="cta-strip">
    <div class="wrap">
        <h3>Prefer to talk it through first?</h3>
        <a href="{{ route('contact') }}" class="btn btn-navy">Contact Our Team</a>
    </div>
</div>
@endsection
