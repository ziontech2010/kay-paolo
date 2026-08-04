@extends('layouts.site')

@php
    $user = session('zion.user', []);
@endphp

@section('title', 'Create Shipment | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Create Shipment</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Create Shipment</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap quote-page-grid">
        <div class="page-back-actions" style="grid-column: 1 / -1">
            <button type="button" class="btn btn-secondary btn-back" data-go-back>&larr; Back</button>
        </div>

        <form id="createShipmentForm" class="contact-form" style="padding: 0; background: none; border: none; box-shadow: none; grid-column: 1 / -1">
            <div class="shipment-card selected-service-card" style="margin-bottom: 30px; border: 1px solid var(--line); border-radius: var(--radius); background: #f8fafc; padding: 22px 28px; box-shadow: 0 4px 15px -2px rgba(15, 37, 64, 0.04)">
                <div style="font-size: 11px; font-weight: 700; color: var(--ink-400); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px">
                    Selected Service
                </div>
                <div class="api-alert error" id="selectedServiceNotice" hidden>
                    No quote option is selected yet. Go back to quote results and choose Book Now.
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 20px; align-items: center" class="form-row-5">
                    <div>
                        <h3 id="selectedServiceName" style="font-family: 'Inter', sans-serif; font-size: 22px; font-weight: 800; color: var(--navy-900); margin: 0 0 4px 0; text-transform: none; letter-spacing: 0">
                            Service pending
                        </h3>
                        <span id="selectedServiceTotal" style="font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 700; color: var(--gold-500)">USD 0.00</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 11px; font-weight: 700; color: var(--ink-400); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px">Quote</span>
                        <span class="mono" id="selectedQuoteId" style="font-size: 14.5px; font-weight: 600; color: var(--navy-900)">-</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 11px; font-weight: 700; color: var(--ink-400); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px">Carrier</span>
                        <span id="selectedCarrier" style="font-size: 14.5px; font-weight: 600; color: var(--navy-900)">Kay Paolo</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 11px; font-weight: 700; color: var(--ink-400); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px">Arrives On</span>
                        <span id="selectedArrivesOn" style="font-size: 14.5px; font-weight: 600; color: var(--navy-900)">-</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 11px; font-weight: 700; color: var(--ink-400); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px">Delivered By</span>
                        <span id="selectedDeliveredBy" style="font-size: 14.5px; font-weight: 600; color: var(--navy-900)">-</span>
                    </div>
                </div>
            </div>

            <div class="create-shipment-grid">
                <div class="shipment-card" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0">
                    <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                        Shipper Information
                    </div>
                    <div class="shipment-card-body" style="flex-grow: 1">
                        <div class="field">
                            <label>Name</label>
                            <input type="text" id="shipmentFromName" value="{{ $user['name'] ?? 'Therlande Louis Jean' }}" required>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label>Email</label>
                                <input type="email" id="shipmentFromEmail" value="{{ $user['email'] ?? 'zlionline@gmail.com' }}" required>
                            </div>
                            <div class="field">
                                <label>Phone</label>
                                <input type="text" id="shipmentFromPhone" value="{{ $user['phone'] ?? $user['mobile'] ?? '7867027700' }}" required>
                            </div>
                        </div>
                        <div class="form-row-3">
                            <div class="field" style="grid-column: span 2">
                                <label>Country *</label>
                                <select id="shipmentFromCountry" data-country-select required>
                                    <option value="US">United States</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>Zip Code</label>
                                <input type="text" id="shipmentFromZip" value="{{ $user['shipper_zip'] ?? $user['zip'] ?? '33162' }}" required>
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label>Address *</label>
                                <input type="text" id="shipmentFromAddress" value="{{ $user['shipper_address'] ?? $user['address'] ?? '1117 NE 163rd St.' }}" required>
                            </div>
                            <div class="field">
                                <label>Apt/Ste/Unit</label>
                                <input type="text" id="shipmentFromApt" value="" placeholder="e.g. Apt 4B">
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label>City *</label>
                                <input type="text" id="shipmentFromCity" value="{{ $user['shipper_city'] ?? $user['city'] ?? 'North Miami Beach' }}" required>
                            </div>
                            <div class="field">
                                <label>State *</label>
                                <input type="text" id="shipmentFromState" value="{{ $user['shipper_state'] ?? $user['state'] ?? 'FL' }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="shipment-card" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0">
                    <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                        Consignee Information
                    </div>
                    <div class="shipment-card-body" style="flex-grow: 1">
                        <div class="field">
                            <label>Consignee Name</label>
                            <input type="text" id="shipmentToName" placeholder="Consignee name" required>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label>Phone</label>
                                <input type="text" id="shipmentToPhone" placeholder="Phone" required>
                            </div>
                            <div class="field">
                                <label>Home Phone</label>
                                <input type="text" id="shipmentToHomePhone" placeholder="Home phone">
                            </div>
                        </div>
                        <div class="form-row-3">
                            <div class="field" style="grid-column: span 2">
                                <label>Country *</label>
                                <select id="shipmentToCountry" data-country-select required>
                                    <option value="">Select Country</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>Zip Code</label>
                                <input type="text" id="shipmentToZip" placeholder="Zip code">
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label>Address *</label>
                                <input type="text" id="shipmentToAddress" placeholder="Address" required>
                            </div>
                            <div class="field">
                                <label>Apt/Ste/Unit</label>
                                <input type="text" id="shipmentToApt" placeholder="Suite or unit">
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field">
                                <label>City *</label>
                                <input type="text" id="shipmentToCity" placeholder="City" required>
                            </div>
                            <div class="field">
                                <label>State *</label>
                                <input type="text" id="shipmentToState" placeholder="State" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="shipment-card" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0">
                    <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                        Package Information
                    </div>
                    <div class="shipment-card-body" style="flex-grow: 1">
                        <div id="shipmentPackageSummary" class="api-inline-result success">Package details will be filled from your selected quote.</div>
                        <div class="field">
                            <label>Package Description</label>
                            <textarea id="shipmentPackageDescription" placeholder="Package description"></textarea>
                        </div>
                        <div class="checkbox-field" style="margin-top: 16px">
                            <input type="checkbox" id="shipmentFragile" disabled>
                            <label for="shipmentFragile">Fragile shipment</label>
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
                                <label>Delivery location *</label>
                                <select id="shipmentDeliveryLocation" required>
                                    <option value="">-- Select Delivery Location --</option>
                                    <option value="Pickup in Office">Pickup in Office</option>
                                    <option value="Home Delivery">Home Delivery</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>Payment Type</label>
                                <select id="shipmentPaymentType" data-payment-options>
                                    <option value="">Loading payment options...</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-gold btn-block" style="font-size: 16px; font-weight: 700; padding: 14px 28px; border-radius: 8px">
                                Complete Shipment
                            </button>
                            <div id="shippingResult"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="api-loader" id="shippingLoader" hidden>
            <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Processing shipping">
        </div>
    </div>
</section>
@endsection
