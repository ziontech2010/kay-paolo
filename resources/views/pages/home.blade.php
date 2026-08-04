@extends('layouts.site')

@php
    $content = app(\App\Services\KayPaoloContent::class)->all();
@endphp

@section('title', 'Kay Paolo Shipping | Global Freight and Logistics')
@section('description', $content['meta_description'])

@section('content')
<section class="hero" id="home">
    <div class="wrap hero-grid">
        <div>
            <div class="eyebrow">Ocean / Air / Land / Since 2011</div>
            <h1>No Matter Where,<br>We Ship <em>Globally.</em></h1>
            <p class="lead">
                Kay Paolo Shipping moves freight across 120+ countries with a single point of contact from pickup to
                final-mile delivery - full visibility, no guesswork.
            </p>
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
                <button data-tab="track" id="trackTabBtn">Track</button>
            </div>
            <div class="qc-body">
                <div class="tab-panel active" id="panel-quote">
                    <div class="field-row">
                        <div class="field">
                            <label for="destCountry">To Destination</label>
                            <select id="destCountry" data-country-select>
                                <option value="">Select Destination</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="cargoWeight">Weight (LBS)</label>
                            <input type="number" id="cargoWeight" placeholder="e.g. 250">
                        </div>
                    </div>
                    <a href="{{ route('quote') }}" class="btn btn-navy btn-block">Get A Quote</a>
                    <p class="qc-note">Sign in to generate live Kay Paolo quotes.</p>
                </div>

                <div class="tab-panel" id="panel-track">
                    <div class="field">
                        <label for="trackNum">Tracking / Waybill Number</label>
                        <input type="text" id="trackNum" class="mono" placeholder="123456">
                    </div>
                    <a href="{{ route('tracking') }}" class="btn btn-navy btn-block">Track &amp; Trace</a>
                    <p class="qc-note">
                        Need help changing your delivery?
                        <a href="{{ route('contact') }}" style="color: var(--teal-600); font-weight: 600">Click here</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="services">
    <div class="wrap">
        <div class="section-head center">
            <div class="eyebrow" style="justify-content: center">What We Move</div>
            <h2>Freight solutions built around your cargo</h2>
            <p>
                From a single pallet to a full container load, we match the mode, route and paperwork to what actually
                needs to move - and when.
            </p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-card-bg">
                    <svg viewBox="0 0 120 100" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 82 C30 79 50 85 70 82 C90 79 110 82 110 82" stroke-width="1.5"></path>
                        <path d="M15 82 L22 62 H98 L105 82 Z" fill="currentColor" fill-opacity="0.05"></path>
                        <rect x="30" y="47" width="16" height="15"></rect>
                        <rect x="48" y="37" width="16" height="25"></rect>
                        <rect x="66" y="44" width="18" height="18"></rect>
                    </svg>
                </div>
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2.5l-2-7.5H5l-2 7.5V18zm6-11V4h6v3H9z M6 13h3v-2.5H6V13zm4.5 0h3v-2.5h-3V13zm4.5 0h3v-2.5h-3V13z"></path></svg>
                </div>
                <h3>Ocean Freight</h3>
                <p>Reliable global sea freight for international cargo with secure handling across major ports.</p>
            </div>
            <div class="service-card">
                <div class="service-card-bg">
                    <svg viewBox="0 0 120 100" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 75 H110" stroke-width="1.5"></path>
                        <path d="M70 40 H105 L110 50 V75 H70 Z" fill="currentColor" fill-opacity="0.05"></path>
                        <rect x="10" y="45" width="26" height="30" rx="1" fill="currentColor" fill-opacity="0.03"></rect>
                        <rect x="40" y="45" width="26" height="30" rx="1" fill="currentColor" fill-opacity="0.03"></rect>
                        <circle cx="78" cy="75" r="5" fill="currentColor"></circle>
                        <circle cx="102" cy="75" r="5" fill="currentColor"></circle>
                    </svg>
                </div>
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="3" width="16" height="14" rx="2"></rect><circle cx="8" cy="19" r="2"></circle><circle cx="16" cy="19" r="2"></circle></svg>
                </div>
                <h3>Rail Freight</h3>
                <p>Efficient long-distance movement, connecting land routes for larger logistics needs.</p>
            </div>
            <div class="service-card">
                <div class="service-card-bg">
                    <svg viewBox="0 0 120 100" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 78 H110" stroke-width="1.5"></path>
                        <path d="M82 78 V52 H95 L106 64 V78 Z" fill="currentColor" fill-opacity="0.05"></path>
                        <rect x="15" y="40" width="64" height="38" rx="2" fill="currentColor" fill-opacity="0.05"></rect>
                        <circle cx="28" cy="78" r="6" fill="currentColor"></circle>
                        <circle cx="88" cy="78" r="6" fill="currentColor"></circle>
                    </svg>
                </div>
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="5" width="13" height="11" rx="1"></rect><path d="M15 8h4.5l3 3.5V16h-7.5V8z"></path><circle cx="6" cy="18" r="2.5"></circle><circle cx="18" cy="18" r="2.5"></circle></svg>
                </div>
                <h3>Land Freight</h3>
                <p>Flexible trucking and local distribution with safe door-to-door delivery options.</p>
            </div>
            <div class="service-card">
                <div class="service-card-bg">
                    <svg viewBox="0 0 120 100" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 50 C20 45 40 43 70 45 C85 46 100 48 108 52 C112 54 112 56 108 58 C100 62 85 64 70 65 C40 67 20 65 20 50 Z" fill="currentColor" fill-opacity="0.05"></path>
                        <path d="M22 47 L12 28 C15 27 18 28 20 30 L32 46 Z" fill="currentColor" fill-opacity="0.08"></path>
                    </svg>
                </div>
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"></path></svg>
                </div>
                <h3>Air Freight</h3>
                <p>Premium air cargo for time-critical shipments and fast global delivery.</p>
            </div>
        </div>
    </div>
</section>

<section class="about" id="about">
    <div class="wrap about-grid">
        <div class="about-art about-photo-stack" aria-hidden="true">
            <img class="about-photo-primary" src="{{ asset($content['who_image_primary']) }}" alt="">
            <img class="about-photo-secondary" src="{{ asset($content['who_image_secondary']) }}" alt="">
        </div>
        <div>
            <div class="eyebrow">Who We Are</div>
            <h2>{{ $content['who_headline'] }}</h2>
            <p style="margin-top: 18px; color: var(--ink-600); font-size: 15.5px; line-height: 1.75">
                {{ $content['who_body'] }}
            </p>
            <ul class="about-list">
                <li><span class="tick">&#10003;</span> Safe and secure handling</li>
                <li><span class="tick">&#10003;</span> Affordable, transparent pricing</li>
                <li><span class="tick">&#10003;</span> Experienced logistics professionals</li>
                <li><span class="tick">&#10003;</span> Real-time tracking technology</li>
            </ul>
            <a href="{{ route('about') }}" class="btn btn-navy" style="margin-top: 30px">Learn About Us</a>
        </div>
    </div>
</section>

<section class="stats">
    <div class="wrap stats-grid">
        <div class="stat"><b data-count="6">0</b><span>Regional Hubs</span></div>
        <div class="stat"><b data-count="120">0</b><span>Countries Worldwide</span></div>
        <div class="stat"><b data-count="340" data-suffix="+">0</b><span>Fleet Vehicles</span></div>
        <div class="stat"><b data-count="180" data-suffix="+">0</b><span>Logistics Professionals</span></div>
        <div class="stat"><b data-count="95" data-suffix="k">0</b><span>Sq.Ft. Warehousing</span></div>
        <div class="stat"><b data-count="8" data-suffix="k">0</b><span>Containers / Year</span></div>
    </div>
</section>

<section class="process">
    <div class="wrap">
        <div class="section-head">
            <div class="eyebrow">How It Works</div>
            <h2>Three steps, one point of contact</h2>
            <p>The same Kay Paolo interface stays with your shipment from quote to proof of delivery.</p>
        </div>
        <div class="process-grid">
            <div class="process-step">
                <span class="step-num">01 <span class="step-dash">---</span></span>
                <h3>{{ $content['process_step_1_title'] }}</h3>
                <p>{{ $content['process_step_1_body'] }}</p>
            </div>
            <div class="process-step">
                <span class="step-num">02</span>
                <h3>Pickup &amp; Tracking</h3>
                <p>Create a shipment after quote selection and keep tracking visible from the Kay Paolo portal.</p>
            </div>
            <div class="process-step">
                <span class="step-num">03</span>
                <h3>Safe &amp; Timely Delivery</h3>
                <p>View shipment documents, tracking payloads, and customer shipping data from one Kay Paolo flow.</p>
            </div>
        </div>
    </div>
</section>

<div class="cta-strip">
    <div class="wrap">
        <h3>Let's request a schedule for a free consultation.</h3>
        <a href="{{ route('contact') }}" class="btn btn-navy">Get In Touch</a>
    </div>
</div>

<section id="contact">
    <div class="wrap">
        <div class="section-head">
            <div class="eyebrow">Get In Touch</div>
            <h2>Get in touch with us</h2>
            <p>Kay Paolo is here to provide the best logistics solutions across the globe.</p>
        </div>
        <div class="contact-grid">
            <div class="contact-info">
                <div class="info-card"><div class="ic"></div><div><h4>Support Center 24/7</h4><p>(732) 898-9303</p></div></div>
                <div class="info-card"><div class="ic"></div><div><h4>Our Location</h4><p>414 Main St,<br>Asbury Park, NJ 07712</p></div></div>
                <div class="info-card"><div class="ic"></div><div><h4>Write To Us</h4><p>info@kaypaoloshipping.com</p></div></div>
            </div>
            <form class="contact-form" data-inline-confirm>
                <div class="form-row">
                    <div class="field"><label for="homeName">Your Name</label><input type="text" id="homeName" placeholder="Enter your name" required></div>
                    <div class="field"><label for="homeEmail">Your Email</label><input type="email" id="homeEmail" placeholder="Enter your email" required></div>
                </div>
                <div class="field"><label for="homeMsg">Message</label><textarea id="homeMsg" required placeholder="Tell us about your shipment - origin, destination, and approximate weight."></textarea></div>
                <button type="submit" class="btn btn-gold">Send Message</button>
                <p class="form-note">Thanks - your message has been queued for our logistics desk.</p>
            </form>
        </div>
    </div>
</section>
@endsection
