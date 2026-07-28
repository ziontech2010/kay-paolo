@extends('layouts.site')

@section('title', 'Shipment Confirmation | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Create Shipment</h1>
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="sep">/</span>
            <a href="{{ route('quote') }}">Get A Quote</a>
            <span class="sep">/</span>
            <span>Create Shipment</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<script>
    (function kayPaoloMarkConfirmationSeen() {
        try {
            var raw = window.localStorage.getItem('kayPaoloShipmentResponse');
            if (!raw) return;

            var shipment = JSON.parse(raw);
            var response = shipment.response || {};
            var responseData = response.data || {};
            var shipping = response.shipping_data || response.shipping || responseData.shipping_data || responseData.shipping || {};
            var payload = shipment.payload || {};
            var token = response.tracking_number
                || response.invoice_num
                || response.awb
                || responseData.tracking_number
                || shipping.tracking_number
                || shipping.invoice_num
                || payload.tracking_number
                || payload.quote_id
                || '';

            if (token) {
                window.localStorage.setItem('kayPaoloShipmentConfirmationSeen', String(token));
            }
        } catch (error) {}
    })();
</script>

<section class="page-follows-banner">
    <div class="wrap shipment-confirmation-wrap" data-shipment-confirmation>
        <div class="shipment-success-alert">
            <span>Shipment booked successfully.</span>
        </div>

        <div class="shipment-confirm-hero-card">
            <h2>VIEW LABELS DOCUMENTS AND RECEIPT</h2>
            <p class="confirm-desc">
                Your shipment has been created successfully. Use the document links below to view or print the shipment labels and receipt.
            </p>

            <div class="confirm-details-grid">
                <div class="confirm-detail-box">
                    <span class="confirm-detail-label">SHIPMENT NUMBER</span>
                    <span class="confirm-detail-value" id="shipmentNoDisplay">Pending</span>
                </div>
                <div class="confirm-detail-box">
                    <span class="confirm-detail-label">CARRIER DETAILS</span>
                    <span class="confirm-detail-value" id="carrierDisplay">ZION</span>
                </div>
            </div>

            <div class="confirm-actions-row">
                <a href="{{ route('receipt.a4') }}" target="_blank" class="btn btn-gold" id="openLabelBtn">Open Label</a>
                <a href="{{ route('receipt') }}" target="_blank" class="btn btn-gold" id="openReceiptBtn">Open Receipt</a>
            </div>
        </div>

        <div class="return-home-container">
            <a href="{{ route('home') }}" class="btn btn-gold">Return to Home</a>
        </div>
    </div>
</section>
@endsection
