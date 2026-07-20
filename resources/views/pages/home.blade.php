@extends('layouts.site')

@section('title', 'Kay Paolo Shipping | Global Freight and Logistics')

@section('content')
<section class="hero" id="home" style="padding-bottom:0;">
    <div class="wrap hero-grid">
        <div>
            <div class="eyebrow">Ocean / Air / Land / Since 2011</div>
            <h1>No Matter Where,<br>We Ship <em>Globally.</em></h1>
            <p class="lead">Kay Paolo Shipping gives Zion users a branded portal for quotes, shipments, documents, and tracking while dev Zion Shipping remains the API source of truth.</p>
            <div class="hero-actions">
                <a href="{{ route('quote') }}" class="btn btn-gold">Request A Quote</a>
                <a href="{{ route('tracking') }}" class="btn btn-outline">Track A Shipment</a>
            </div>
            <div class="hero-trust">
                <div><b>120+</b><span>Countries Served</span></div>
                <div><b>8,400</b><span>Containers / Year</span></div>
                <div><b>24/7</b><span>Support Desk</span></div>
            </div>
        </div>

        <div class="quote-card" id="quote">
            <div class="qc-tabs">
                <button class="active" data-tab="quote">Quote</button>
                <button data-tab="track">Track</button>
            </div>
            <div class="qc-body">
                <div class="tab-panel active" id="panel-quote">
                    <div class="field">
                        <label for="destCountry">To Destination</label>
                        <select id="destCountry">
                            <option>Haiti</option>
                            <option>Dominican Republic</option>
                            <option>United States</option>
                            <option>Canada</option>
                            <option>Jamaica</option>
                        </select>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="cargoWeight">Weight (LBS)</label>
                            <input type="number" id="cargoWeight" placeholder="e.g. 25">
                        </div>
                        <div class="field">
                            <label for="cargoMode">Mode</label>
                            <select id="cargoMode"><option>Ocean Freight</option><option>Air Freight</option><option>Land Freight</option></select>
                        </div>
                    </div>
                    <a href="{{ route('quote') }}" class="btn btn-navy btn-block">Get A Quote</a>
                    <p class="qc-note">Login with your Zion account for live rates from dev Zion Shipping.</p>
                </div>

                <div class="tab-panel" id="panel-track">
                    <div class="field">
                        <label for="trackNum">Tracking / Waybill Number</label>
                        <input type="text" id="trackNum" class="mono" placeholder="Enter tracking number">
                    </div>
                    <a href="{{ route('tracking') }}" class="btn btn-navy btn-block">Track and Trace</a>
                    <p class="qc-note">Tracking is sent to Zion through Kay Paolo's API bridge.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="wrap lane" aria-hidden="true">
        <svg viewBox="0 0 900 120" preserveAspectRatio="none">
            <line x1="40" y1="60" x2="860" y2="60" stroke="#3A567A" stroke-width="2" class="dash"/>
            <circle cx="40" cy="60" r="6" fill="#E4C983"/>
            <circle cx="860" cy="60" r="6" fill="#E4C983"/>
            <text x="40" y="88" fill="#8CA0B8" font-family="IBM Plex Mono" font-size="12">NEWARK, US</text>
            <text x="800" y="88" fill="#8CA0B8" font-family="IBM Plex Mono" font-size="12">PORT-AU-PRINCE, HT</text>
            <g class="ship" transform="translate(40,44)">
                <path d="M0 16 L26 16 L22 26 L4 26 Z" fill="#C89B3C"/>
                <rect x="8" y="6" width="10" height="10" fill="#F6F2E9"/>
            </g>
        </svg>
    </div>
</section>

<section id="services">
    <div class="wrap">
        <div class="section-head center">
            <div class="eyebrow" style="justify-content:center;">What We Move</div>
            <h2>Freight solutions built around your cargo</h2>
            <p>From a single document to a full container load, Kay Paolo uses Zion's live rating and shipping engine behind a clean branded portal.</p>
        </div>
        <div class="services-grid">
            <div class="service-card"><span class="num">01 / Ocean</span><div class="service-icon"></div><h3>Ocean Freight</h3><p>Container and package movement with documents, receipts, and labels returned through the API.</p></div>
            <div class="service-card"><span class="num">02 / Air</span><div class="service-icon"></div><h3>Air Freight</h3><p>Fast options across Zion, UPS, FedEx, and USPS when supported by the destination.</p></div>
            <div class="service-card"><span class="num">03 / Land</span><div class="service-icon"></div><h3>Land Freight</h3><p>Domestic delivery requests and tracking visibility from pickup through delivery.</p></div>
            <div class="service-card"><span class="num">04 / Customs</span><div class="service-icon"></div><h3>Documents</h3><p>Shipment labels, receipts, and commercial invoice data can be returned after shipment creation.</p></div>
        </div>
    </div>
</section>

<section class="process">
    <div class="wrap">
        <div class="section-head">
            <div class="eyebrow">API Workflow</div>
            <h2>Kay Paolo UI, Zion API engine</h2>
            <p>The Kay Paolo project stores no Zion business records locally. It logs in, quotes, ships, and tracks through dev Zion Shipping as the third-party system.</p>
        </div>
        <div class="process-grid">
            <div class="process-step"><span class="step-num">01</span><h3>Login</h3><p>Any active Zion user can sign in and Kay Paolo receives their role payload.</p></div>
            <div class="process-step"><span class="step-num">02</span><h3>Quote</h3><p>The quote form posts to Kay Paolo, which forwards to `/api/kay-paolo/get-quote-result`.</p></div>
            <div class="process-step"><span class="step-num">03</span><h3>Ship and Track</h3><p>Shipment creation and tracking use Kay Paolo routes that proxy dev Zion responses.</p></div>
        </div>
    </div>
</section>
@endsection
