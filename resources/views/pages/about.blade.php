@extends('layouts.site')

@section('title', 'About Us | Kay Paolo Shipping')

@section('banner')
<div class="page-banner"><div class="wrap"><h1>About Us</h1><div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>About</span></div></div></div>
@endsection

@section('content')
<section class="about">
    <div class="wrap about-grid">
        <div class="about-art" aria-hidden="true"><div class="plate plate-1"></div><div class="plate plate-2"></div></div>
        <div>
            <div class="eyebrow">Who We Are</div>
            <h2>Kay Paolo branding, Zion-powered operations</h2>
            <p class="muted-text">This project keeps Kay Paolo's customer-facing UI in Blade while treating dev Zion Shipping as the third-party API for login, quotes, shipments, and tracking.</p>
            <ul class="about-list">
                <li><span class="tick">OK</span> Separate Kay Paolo views and routes</li>
                <li><span class="tick">OK</span> Additive Zion API namespace</li>
                <li><span class="tick">OK</span> Role-aware Zion authentication</li>
                <li><span class="tick">OK</span> Quote and shipment GIF loaders</li>
            </ul>
            <a href="{{ route('quote') }}" class="btn btn-navy" style="margin-top:30px;">Create Shipment</a>
        </div>
    </div>
</section>
@endsection
