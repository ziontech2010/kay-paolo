@extends('layouts.site')

@section('title', 'Services | Kay Paolo Shipping')

@section('banner')
<div class="page-banner"><div class="wrap"><h1>Services</h1><div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Services</span></div></div></div>
@endsection

@section('content')
<section>
    <div class="wrap">
        <div class="section-head center">
            <div class="eyebrow" style="justify-content:center;">Services</div>
            <h2>Shipping tools connected to dev Zion</h2>
            <p>Every action stays inside Kay Paolo's Blade interface while operational data is requested from Zion's API.</p>
        </div>
        <div class="services-grid">
            <div class="service-card" id="ocean"><span class="num">01</span><h3>Ocean Freight</h3><p>International and Caribbean shipment workflows through Zion carrier products.</p></div>
            <div class="service-card" id="air"><span class="num">02</span><h3>Air Freight</h3><p>Fast rate cards and delivery estimates for supported destinations.</p></div>
            <div class="service-card" id="land"><span class="num">03</span><h3>Land Freight</h3><p>Domestic pickup, delivery, and customer shipment history support.</p></div>
            <div class="service-card" id="rail"><span class="num">04</span><h3>Documents</h3><p>Labels, receipts, and commercial invoice metadata returned after shipment creation.</p></div>
        </div>
    </div>
</section>
@endsection
