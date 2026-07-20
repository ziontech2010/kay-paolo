@extends('layouts.site')

@section('title', 'Track A Shipment | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Package Tracking</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Tracking</span></div>
    </div>
</div>
@endsection

@section('content')
<section>
    <div class="wrap">
        <div class="section-head">
            <div class="eyebrow">Track Your Shipment</div>
            <h2>Enter a tracking number to see live status</h2>
            <p>Kay Paolo forwards tracking requests to dev Zion Shipping through the Kay Paolo API namespace.</p>
        </div>

        <form class="contact-form tracking-form" id="trackingForm">
            <div class="field">
                <label for="tracking_number">Tracking / Waybill Number</label>
                <input type="text" id="tracking_number" class="mono" placeholder="Enter tracking number" required>
            </div>
            <button type="submit" class="btn btn-gold btn-block">Track Shipment</button>
        </form>

        <div class="api-loader" id="trackingLoader" hidden>
            <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Processing tracking">
        </div>
        <div class="result-panel" id="trackingResult"></div>

        <div class="agent-box">
            <h3>Need Help With Payment Or A Delivery Change?</h3>
            <p>Reach a Kay Paolo shipping agent directly and they will help confirm the next step for your shipment.</p>
            <div class="agent-contacts">
                <div>+1 (201) 555-0148</div>
                <div>info@kaypaoloshipping.com</div>
            </div>
        </div>
    </div>
</section>
@endsection
