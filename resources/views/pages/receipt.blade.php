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
<section class="page-follows-banner">
    <div class="wrap" style="max-width: 980px">
        <div class="shipment-card">
            <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                Shipment Receipt
            </div>
            <div class="shipment-card-body">
                <div class="form-row-3">
                    <div>
                        <span class="meta-label">Tracking Number</span>
                        <h3 id="receiptTrackingNumber" class="mono" style="margin-top: 6px">Pending</h3>
                    </div>
                    <div>
                        <span class="meta-label">Status</span>
                        <h3 id="receiptStatus" style="margin-top: 6px">Booked</h3>
                    </div>
                    <div>
                        <span class="meta-label">Total</span>
                        <h3 id="receiptTotal" style="margin-top: 6px">USD 0.00</h3>
                    </div>
                </div>
                <div id="receiptSummary" class="api-raw" style="margin-top: 22px">Shipment details will appear here after booking.</div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px">
                    <a href="{{ route('receipt.a4') }}" class="btn btn-gold">Open A4 Receipt</a>
                    <a href="{{ route('invoice') }}" class="btn btn-navy">Open Invoice</a>
                    <a href="{{ route('tracking') }}" class="btn btn-outline">Track Shipment</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
