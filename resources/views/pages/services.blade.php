@extends('layouts.site')

@section('title', 'Services | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Our Services</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Services</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap">
        <div class="service-zig" id="ocean">
            <div class="sz-art">
                <img src="https://images.unsplash.com/photo-1494412574643-ff11b0a5c1c3?auto=format&fit=crop&w=800&q=80" alt="Ocean freight transport">
            </div>
            <div class="sz-text">
                <span class="idx">SERVICE 01</span>
                <h2>Ocean Transport</h2>
                <p>Cost-effective solutions for large and heavy shipments with complete port-to-port visibility.</p>
                <p class="callout">We manage each step with clean documents, route clarity, and steady communication.</p>
            </div>
        </div>

        <div class="service-zig rev" id="air">
            <div class="sz-art">
                <img src="https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80" alt="Air freight transport">
            </div>
            <div class="sz-text">
                <span class="idx">SERVICE 02</span>
                <h2>Air Freight Transport</h2>
                <p>Fast, dependable options for international destinations when speed matters most.</p>
                <p class="callout">Kay Paolo sends quote requests through the live shipping bridge and presents returned rate cards here.</p>
            </div>
        </div>

        <div class="service-zig" id="land">
            <div class="sz-art">
                <img src="https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=800&q=80" alt="Road freight transport">
            </div>
            <div class="sz-text">
                <span class="idx">SERVICE 03</span>
                <h2>Road Transport</h2>
                <p>Flexible local and regional transport for timely, secure deliveries.</p>
                <p class="callout">Live tracking uses the Kay Paolo API namespace.</p>
            </div>
        </div>

        <div class="service-zig rev" id="rail">
            <div class="sz-art">
                <img src="https://images.unsplash.com/photo-1474487548417-781cb71495f3?auto=format&fit=crop&w=800&q=80" alt="Rail freight transport">
            </div>
            <div class="sz-text">
                <span class="idx">SERVICE 04</span>
                <h2>Shipment Documents</h2>
                <p>Labels, receipts, and shipment response payloads are available after shipment creation.</p>
                <p class="callout">Kay Paolo keeps shipment documents and customer-facing workflows in one place.</p>
            </div>
        </div>
    </div>
</section>

<div class="cta-strip">
    <div class="wrap">
        <h3>Tell us what's moving - we'll match the mode.</h3>
        <a href="{{ route('quote') }}" class="btn btn-navy">Get A Quote</a>
    </div>
</div>
@endsection
