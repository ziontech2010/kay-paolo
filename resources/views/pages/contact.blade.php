@extends('layouts.site')

@section('title', 'Contact | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Get In Touch</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Contact Us</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap">
        <div class="section-head">
            <div class="eyebrow">Get In Touch</div>
            <h2>Get in touch with us</h2>
            <p>Reach out to our experts for a seamless shipping experience across the globe.</p>
        </div>

        <div class="contact-grid">
            <div class="contact-info">
                <div class="info-card"><div class="ic"></div><div><h4>Support Center 24/7</h4><p>(732) 898-9303</p></div></div>
                <div class="info-card"><div class="ic"></div><div><h4>Our Location</h4><p>414 Main St,<br>Asbury Park, NJ 07712</p></div></div>
                <div class="info-card"><div class="ic"></div><div><h4>Write To Us</h4><p>info@kaypaoloshipping.com</p></div></div>
                <div class="info-card"><div class="ic"></div><div><h4>Business Hours</h4><p>MON - SAT: 10 a.m. - 6 p.m.</p></div></div>
                <div class="map-plate" style="height: 170px">
                    <svg width="100%" height="100%" viewBox="0 0 400 170">
                        <rect width="400" height="170" fill="var(--sand-100)"></rect>
                        <path d="M0 100 C 80 70, 140 120, 220 90 S 340 50, 400 80" stroke="var(--line)" stroke-width="2" fill="none"></path>
                        <path d="M0 50 C 100 30, 200 70, 400 30" stroke="var(--line)" stroke-width="1.5" fill="none"></path>
                        <path d="M120 0 L120 170 M280 0 L280 170" stroke="var(--line)" stroke-width="1"></path>
                        <circle cx="230" cy="85" r="6" fill="var(--gold-500)"></circle>
                        <text x="246" y="89" font-family="IBM Plex Mono" font-size="11" fill="#7C8894">Asbury Park, NJ</text>
                    </svg>
                </div>
            </div>

            <form class="contact-form" id="contactForm">
                <div class="form-row">
                    <div class="field"><label for="cName">Your Name</label><input type="text" id="cName" placeholder="Enter your name" required></div>
                    <div class="field"><label for="cEmail">Your Email</label><input type="email" id="cEmail" placeholder="Enter your email" required></div>
                </div>
                <div class="field">
                    <label for="cSubject">Subject</label>
                    <select id="cSubject">
                        <option>General Inquiry</option>
                        <option>Get A Quote</option>
                        <option>Existing Shipment</option>
                        <option>Partnership</option>
                    </select>
                </div>
                <div class="field"><label for="cMsg">Message</label><textarea id="cMsg" required placeholder="Tell us about your shipment - origin, destination, and approximate weight."></textarea></div>
                <button type="submit" class="btn btn-gold">Send Message</button>
            </form>
        </div>
    </div>
</section>
@endsection

@push('modals')
<div class="confirm-overlay" id="confirmOverlay" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="confirm-card">
        <div class="confirm-icon">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        <h3 id="confirmTitle">Thank You!</h3>
        <div class="confirm-divider"></div>
        <p>Your message has been received. A logistics specialist will get back to you within <strong>one business day</strong>.</p>
        <button class="btn-confirm-close" id="confirmClose">Got It</button>
    </div>
</div>
@endpush
