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
<section class="page-follows-banner">
    <div class="wrap" style="max-width: 760px">
        <div class="tracking-search-card" style="background: #ffffff; border: 1px solid var(--line); border-radius: var(--radius); padding: 50px; box-shadow: var(--shadow); text-align: center">
            <div class="tracking-search-icon" style="display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11.5" cy="10.5" r="6.5" stroke="var(--gold-500)" stroke-width="2.5" fill="none"></circle>
                    <path d="M11.5 7.5C10.1 7.5 9 8.6 9 10C9 11.8 11.5 14.5 11.5 14.5S14 11.8 14 10C14 8.6 12.9 7.5 11.5 7.5ZM11.5 11C10.95 11 10.5 10.55 10.5 10C10.5 9.45 10.95 9 11.5 9C12.05 9 12.5 9.45 12.5 10C12.5 10.55 12.05 11 11.5 11Z" fill="var(--gold-500)" stroke="none"></path>
                    <line x1="16.5" y1="15.5" x2="21.5" y2="20.5" stroke="var(--gold-500)" stroke-width="2.5" stroke-linecap="round"></line>
                </svg>
            </div>

            <h2 style="font-family: 'Fraunces', serif; font-size: 32px; font-weight: 600; color: var(--navy-950); margin: 0 0 16px">Track Your Shipment</h2>
            <p style="color: var(--ink-600); font-size: 15px; line-height: 1.6; max-width: 500px; margin: 0 auto 32px">
                Enter a tracking number to view the latest shipment status from dev Zion Shipping.
            </p>

            <form id="trackingForm" class="tracking-form" style="max-width: 460px; margin: 0 auto">
                <div class="field" style="margin-bottom: 20px">
                    <input type="text" id="tracking_number" class="mono" placeholder="Enter tracking number" required>
                </div>
                <button type="submit" class="btn btn-navy" style="width: 100%; padding: 14px 20px; font-size: 15px; font-weight: 600; justify-content: center; border-radius: 8px; cursor: pointer; border: none">Track Shipment</button>
            </form>
        </div>
    </div>

    <div class="wrap api-results-wrap">
        <div class="api-loader" id="trackingLoader" hidden>
            <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Processing tracking">
        </div>
        <div class="result-panel" id="trackingResult"></div>
    </div>

    <div class="wrap">
        <div class="agent-box">
            <h3>Need Help With Payment Or A Delivery Change?</h3>
            <p>Reach a Kay Paolo shipping agent directly and they will help confirm the next step for your shipment.</p>
            <div class="agent-contacts">
                <div>(732) 898-9303</div>
                <div>info@kaypaoloshipping.com</div>
            </div>
        </div>
    </div>
</section>
@endsection
