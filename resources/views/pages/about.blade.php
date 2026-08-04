@extends('layouts.site')

@php
    $content = app(\App\Services\KayPaoloContent::class)->all();
@endphp

@section('title', 'About Us | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>About Us</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>About Us</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap about-grid">
        <div class="about-art about-photo-stack" style="position: relative">
            <img class="about-photo-primary" src="{{ asset($content['who_image_primary']) }}" alt="Kay Paolo warehouse team">
            <img class="about-photo-secondary" src="{{ asset($content['who_image_secondary']) }}" alt="Kay Paolo delivery operations">
            <div class="badge-years"><b>14+</b><span>Years Experience</span></div>
        </div>
        <div>
            <div class="eyebrow">Learn About Us</div>
            <h2>We're the fastest-growing global shipping &amp; logistics partner</h2>
            <p class="muted-text">
                Kay Paolo Shipping delivers dependable logistics designed to move cargo across borders with speed and
                care. Whether you are shipping locally or internationally, handling stays smooth from pickup to delivery.
            </p>
            <div class="feature-list-4">
                <div class="fl-item"><span class="dot">&#10003;</span> Fast &amp; Reliable Delivery</div>
                <div class="fl-item"><span class="dot">&#10003;</span> End-to-End Shipping Solutions</div>
                <div class="fl-item"><span class="dot">&#10003;</span> Express &amp; On-Time Shipping</div>
                <div class="fl-item"><span class="dot">&#10003;</span> Worldwide Shipping Services</div>
            </div>
        </div>
    </div>
</section>

<section style="padding-top: 0">
    <div class="wrap about-grid">
        <div class="why-panel">
            <div class="eyebrow" style="color: var(--gold-300)">Why Choose Us?</div>
            <h2 style="font-size: 28px">Your trusted partner in global shipping &amp; logistics</h2>
            <p>
                Kay Paolo uses a branded Laravel Blade interface for authentication, quoting, shipment creation,
                customer lookup, and tracking.
            </p>
            <p style="margin-top: 12px">
                That keeps the customer-facing experience focused on Kay Paolo while allowing users to sign in here
                according to their existing role payload.
            </p>
        </div>
        <div class="about-art about-photo-stack" style="height: 380px">
            <img class="about-photo-primary" src="{{ asset($content['who_image_secondary']) }}" alt="Kay Paolo package scan workflow">
            <img class="about-photo-secondary" src="{{ asset($content['who_image_primary']) }}" alt="Kay Paolo logistics support">
        </div>
    </div>
</section>

<section class="stats" style="padding-top: 70px">
    <div class="wrap stats-grid">
        <div class="stat"><b data-count="6">0</b><span>Regional Hubs</span></div>
        <div class="stat"><b data-count="120">0</b><span>Countries Worldwide</span></div>
        <div class="stat"><b data-count="340" data-suffix="+">0</b><span>Fleet Vehicles</span></div>
        <div class="stat"><b data-count="180" data-suffix="+">0</b><span>Logistics Professionals</span></div>
        <div class="stat"><b data-count="95" data-suffix="k">0</b><span>Sq.Ft. Warehousing</span></div>
        <div class="stat"><b data-count="8" data-suffix="k">0</b><span>Containers / Year</span></div>
    </div>
</section>

<div class="cta-strip">
    <div class="wrap">
        <h3>Ready to move a shipment with a team that shows up?</h3>
        <a href="{{ route('contact') }}" class="btn btn-navy">Get In Touch</a>
    </div>
</div>
@endsection
