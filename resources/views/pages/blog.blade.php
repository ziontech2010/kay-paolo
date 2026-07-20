@extends('layouts.site')

@section('title', 'Blog | Kay Paolo Shipping')

@section('banner')
<div class="page-banner"><div class="wrap"><h1>Blog</h1><div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Blog</span></div></div></div>
@endsection

@section('content')
<section>
    <div class="wrap">
        <div class="section-head"><div class="eyebrow">Updates</div><h2>Logistics notes</h2><p>Reusable content pages kept in Blade for Kay Paolo.</p></div>
        <div class="services-grid">
            <a class="service-card" href="{{ route('blog.post') }}"><span class="num">API</span><h3>How Kay Paolo talks to Zion</h3><p>A clean UI can consume Zion without changing the Zion customer-facing site.</p></a>
            <a class="service-card" href="{{ route('quote') }}"><span class="num">Quote</span><h3>Preparing shipment details</h3><p>Use accurate package dimensions and consignee information for live rates.</p></a>
            <a class="service-card" href="{{ route('tracking') }}"><span class="num">Track</span><h3>Following shipment progress</h3><p>Tracking calls go through the public Kay Paolo endpoint and return Zion data.</p></a>
        </div>
    </div>
</section>
@endsection
