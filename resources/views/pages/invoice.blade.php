@extends('layouts.site')

@section('title', 'Invoice | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Invoice</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Invoice</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap" style="max-width: 980px">
        <div class="shipment-card">
            <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0">
                Invoice Summary
            </div>
            <div class="shipment-card-body">
                <div class="form-row-3">
                    <div><span class="meta-label">Invoice</span><h3 id="invoiceNumber" class="mono" style="margin-top: 6px">Pending</h3></div>
                    <div><span class="meta-label">Amount</span><h3 id="invoiceAmount" style="margin-top: 6px">USD 0.00</h3></div>
                    <div><span class="meta-label">Status</span><h3 id="invoiceStatus" style="margin-top: 6px">Open</h3></div>
                </div>
                <pre id="invoicePayload" class="api-raw">Invoice details will appear here after booking.</pre>
                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px">
                    <a href="{{ route('receipt') }}" class="btn btn-gold">Open Receipt</a>
                    <button type="button" class="btn btn-navy" onclick="window.print()">Print Invoice</button>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
