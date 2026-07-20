@extends('layouts.site')

@section('title', 'Contact | Kay Paolo Shipping')

@section('banner')
<div class="page-banner"><div class="wrap"><h1>Get In Touch</h1><div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Contact</span></div></div></div>
@endsection

@section('content')
<section>
    <div class="wrap contact-grid">
        <div class="contact-info">
            <div class="info-card"><div><h4>Phone</h4><p>+1 (201) 555-0148</p></div></div>
            <div class="info-card"><div><h4>Email</h4><p>info@kaypaoloshipping.com</p></div></div>
            <div class="info-card"><div><h4>Address</h4><p>Port District, Warehouse 7, Newark, NJ 07114</p></div></div>
        </div>
        <form class="contact-form" data-inline-confirm>
            <div class="form-row">
                <div class="field"><label>Name</label><input type="text" required></div>
                <div class="field"><label>Email</label><input type="email" required></div>
            </div>
            <div class="field"><label>Message</label><textarea required></textarea></div>
            <button class="btn btn-navy btn-block" type="submit">Send Message</button>
            <p class="form-note">Thanks. Your message is ready for the Kay Paolo support flow.</p>
        </form>
    </div>
</section>
@endsection
