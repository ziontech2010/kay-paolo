@extends('layouts.site')

@section('title', 'How Ocean Freight Rates Are Calculated | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Ocean Freight Rates</h1>
        <div class="breadcrumb"><a href="{{ route('blog') }}">Blog</a><span class="sep">/</span><span>Freight Rates</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap readable">
        <div class="blog-detail-hero">
            <div class="blog-meta"><span>Ocean Freight</span><span>Jun 18, 2026</span></div>
            <h2>How Ocean Freight Rates Are Actually Calculated</h2>
            <p>
                Every quote starts with the same fundamentals: origin, destination, cargo size, cargo weight, value,
                timing, service level, and required documents. Kay Paolo captures those details in Blade and forwards
                the request to dev Zion Shipping for live carrier pricing.
            </p>
        </div>

        <p>
            The visible price usually combines freight, surcharges, tax, and service fees. When a Kay Paolo user submits
            the quote form, the app keeps Zion Shipping as the system of record and displays the carrier cards returned
            by Zion.
        </p>
        <p>
            Accurate dimensions and consignee data matter. They decide whether the shipment can use a flat-rate product,
            needs a regular package quote, or requires additional handling before shipment creation.
        </p>
        <p>
            This Kay Paolo project keeps the customer-facing UI independent, but the authenticated API token and role
            response still come from Zion. That lets agents, clients, admins, and other Zion users work through the new
            Kay Paolo interface without changing Zion's existing website.
        </p>

        <div class="cta-strip" style="margin-top: 44px">
            <div class="wrap" style="padding: 0">
                <h3>Ready to price your shipment?</h3>
                <a href="{{ route('quote') }}" class="btn btn-navy">Get A Quote</a>
            </div>
        </div>
    </div>
</section>
@endsection
