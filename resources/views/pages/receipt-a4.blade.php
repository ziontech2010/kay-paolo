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
        <div class="a4-receipt-card">
            <div class="shipment-doc-actions no-print">
                <button type="button" class="btn btn-navy" onclick="window.print()">Print Receipt</button>
                <a href="{{ route('receipt') }}" class="btn btn-gold">Open Receipt</a>
                <a href="{{ route('invoice') }}" class="btn btn-outline">Open Invoice</a>
            </div>

            <article class="awb-sheet">
                <header class="awb-header">
                    <div>
                        <img src="{{ asset('kay-paolo/assets/logo/kay-paolo.svg') }}" alt="Kay Paolo Shipping" width="150" height="75">
                        <p>414 Main St, Asbury Park, NJ 07712</p>
                    </div>
                    <div class="awb-code">
                        <div class="barcode-text" id="receiptA4Barcode">*PENDING*</div>
                        <strong id="receiptA4TrackingNumber">Pending</strong>
                    </div>
                </header>

                <div class="awb-number" id="receiptA4LargeNumber">Pending</div>

                <section class="awb-grid">
                    <div>
                        <h3>Sender</h3>
                        <p id="receiptA4Shipper">Kay Paolo Shipping</p>
                    </div>
                    <div>
                        <h3>Receiver</h3>
                        <p id="receiptA4Receiver">Destination customer</p>
                    </div>
                </section>

                <section class="awb-grid">
                    <div>
                        <h3>Package</h3>
                        <p id="receiptA4Package">General merchandise / 1 package</p>
                    </div>
                    <div>
                        <h3>Payment</h3>
                        <p><strong id="receiptA4PaymentType">PAID AT AGENT</strong><br>Total: <span id="receiptA4Total">USD 0.00</span></p>
                    </div>
                </section>
            </article>
        </div>
    </div>
</section>
@endsection
