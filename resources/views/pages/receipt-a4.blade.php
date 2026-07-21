@extends('layouts.site')

@section('title', 'A4 Receipt | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>A4 Receipt</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>A4 Receipt</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap" style="max-width: 900px">
        <div class="shipment-card">
            <div class="shipment-card-header" style="display: flex; justify-content: space-between; align-items: center; text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                <span>Kay Paolo Shipping Receipt</span>
                <button type="button" class="btn btn-gold no-print" onclick="window.print()">Print Receipt</button>
            </div>
            <div class="shipment-card-body">
                <div style="text-align: center; margin-bottom: 22px">
                    <img src="{{ asset('kay-paolo/assets/logo/kay-paolo.svg') }}" alt="Kay Paolo Shipping" width="150" height="75">
                    <h3 id="receiptA4TrackingNumber" class="mono" style="margin-top: 12px">Pending</h3>
                </div>
                <div class="form-row-2">
                    <div class="api-card">
                        <h3>Shipper</h3>
                        <p id="receiptA4Shipper">Kay Paolo Shipping</p>
                    </div>
                    <div class="api-card">
                        <h3>Receiver</h3>
                        <p id="receiptA4Receiver">Destination customer</p>
                    </div>
                </div>
                <pre id="receiptA4Payload" class="api-raw">Shipment details will appear here after booking.</pre>
            </div>
        </div>
    </div>
</section>
@endsection
