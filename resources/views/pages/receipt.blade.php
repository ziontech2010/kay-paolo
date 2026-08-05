@extends('layouts.site')

@section('title', 'Receipt | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Receipt</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Receipt</span></div>
    </div>
</div>
@endsection

@section('content')
<script>
    (function kayPaoloReceiptConfirmationGuard() {
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

            if (token && window.localStorage.getItem('kayPaoloShipmentConfirmationSeen') !== String(token)) {
                window.location.replace(@json(route('shipment.confirmation')));
            }
        } catch (error) {}
    })();
</script>

<section class="page-follows-banner">
    <div class="wrap" style="max-width: 1040px">
        @include('partials.shipment-document', [
            'documentTitle' => 'Receipt',
            'documentKicker' => 'Shipment Receipt',
        ])
    </div>
</section>
@endsection
