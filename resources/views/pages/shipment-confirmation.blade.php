@extends('layouts.site')

@section('title', 'Shipment Confirmation | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Shipment Confirmation</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Shipment Confirmation</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap shipment-confirmation-page" data-shipment-confirmation>
        <div class="payment-success-banner shipment-confirmation-banner">
            <div class="success-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </div>
            <div>
                <h4>Shipment Confirmed</h4>
                <p>Tracking <strong id="confirmationTrackingInline">Pending</strong> is ready for Kay Paolo Shipping.</p>
            </div>
        </div>

        <div class="shipment-confirmation-actions no-print">
            <a href="{{ route('receipt') }}" class="btn btn-gold">Open Receipt</a>
            <a href="{{ route('receipt.a4') }}" class="btn btn-navy">Open A4 Receipt</a>
            <a href="{{ route('invoice') }}" class="btn btn-outline">Open Invoice</a>
            <a href="{{ route('tracking') }}" class="btn btn-outline">Track Shipment</a>
        </div>

        <article class="shipment-confirmation-card">
            <header class="shipment-confirmation-header">
                <div>
                    <span>Confirmation</span>
                    <h2 id="confirmationNumber">Pending</h2>
                    <p id="confirmationStatus">Booked</p>
                </div>
                <img src="{{ asset('kay-paolo/assets/logo/kay-paolo.svg') }}" alt="Kay Paolo Shipping" width="150" height="75">
            </header>

            <section class="shipment-confirmation-grid">
                <div>
                    <span>Tracking</span>
                    <strong id="confirmationTracking">Pending</strong>
                </div>
                <div>
                    <span>Date</span>
                    <strong id="confirmationDate">{{ now()->format('M d, Y') }}</strong>
                </div>
                <div>
                    <span>Service</span>
                    <strong id="confirmationService">Selected service</strong>
                </div>
                <div>
                    <span>Carrier</span>
                    <strong id="confirmationCarrier">Zion</strong>
                </div>
                <div>
                    <span>Delivery Location</span>
                    <strong id="confirmationDeliveryLocation">Pickup in Office</strong>
                </div>
                <div>
                    <span>Estimated Delivery</span>
                    <strong id="confirmationEta">Pending</strong>
                </div>
                <div>
                    <span>Payment</span>
                    <strong id="confirmationPayment">PAID AT AGENT</strong>
                </div>
                <div>
                    <span>Total</span>
                    <strong id="confirmationTotal">USD 0.00</strong>
                </div>
            </section>

            <section class="shipment-confirmation-parties">
                <div>
                    <h3>Shipper</h3>
                    <strong id="confirmationShipperName">Kay Paolo Customer</strong>
                    <p id="confirmationShipperAddress">Shipment address pending</p>
                    <p id="confirmationShipperContact">Contact pending</p>
                </div>
                <div>
                    <h3>Consignee</h3>
                    <strong id="confirmationConsigneeName">Destination Customer</strong>
                    <p id="confirmationConsigneeAddress">Destination address pending</p>
                    <p id="confirmationConsigneeContact">Phone pending</p>
                </div>
            </section>

            <section class="shipment-confirmation-package">
                <div>
                    <span>Package</span>
                    <strong id="confirmationPackageSummary">1 package / 1 lb</strong>
                    <p id="confirmationPackageDescription">Package description pending</p>
                </div>
                <div>
                    <span>Declared Value</span>
                    <strong id="confirmationDeclaredValue">USD 0.00</strong>
                </div>
            </section>
        </article>
    </div>
</section>
@endsection
