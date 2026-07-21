@extends('layouts.site')

@section('title', 'Get A Quote | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Get A Quote</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Get A Quote</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner pull-customer-section">
    <div class="wrap pull-customer-wrap">
        <div class="pull-customer-panel">
            <div class="pull-customer-header">
                <h2>Pull Customer For Quote</h2>
            </div>

            <div class="pull-customer-body">
                <div class="pull-customer-field">
                    <label for="qCustomerLookup">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Customer Phone Number or Account ID
                    </label>
                    <input type="text" id="qCustomerLookup" placeholder="Enter phone number or account ID">
                </div>
                <div class="pull-customer-actions">
                    <button type="button" class="btn btn-gold" id="pullCustomerBtn" data-next="{{ route('quote.details') }}">
                        Pull Customer
                    </button>
                </div>
                <div id="customerLookupResult" class="api-inline-result"></div>
            </div>
        </div>
    </div>
</section>

<div class="cta-strip">
    <div class="wrap">
        <h3>Prefer to talk it through first?</h3>
        <a href="{{ route('contact') }}" class="btn btn-navy">Contact Our Team</a>
    </div>
</div>
@endsection
