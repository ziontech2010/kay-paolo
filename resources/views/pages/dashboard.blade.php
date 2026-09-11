@extends('layouts.site')

@php
    $isAdminRole = in_array((int) ($zionUser['role_id'] ?? 0), [1, 12, 13, 14, 15], true);
    $profileName = old('name', $zionUser['name'] ?? '');
    $profileEmail = old('email', $zionUser['email'] ?? '');
    $profilePhone = old('phone', collect([
        $zionUser['phone'] ?? null,
        $zionUser['mobile'] ?? null,
        $zionUser['shipper_phone'] ?? null,
        $zionUser['shipper_phone_1'] ?? null,
        $zionUser['phone_number'] ?? null,
        $zionUser['phoneNumber'] ?? null,
        $zionUser['mobile_phone'] ?? null,
        $zionUser['mobileNumber'] ?? null,
        $zionUser['mobile_number'] ?? null,
        $zionUser['contact_phone'] ?? null,
        $zionUser['contactPhone'] ?? null,
        $zionUser['telephone'] ?? null,
        $zionUser['telephone_number'] ?? null,
        $zionUser['shipper_contact'] ?? null,
        $zionUser['contact'] ?? null,
    ])->first(fn ($value) => filled($value)) ?? '');
    $profileAddress = old('address', $zionUser['shipper_address'] ?? $zionUser['address'] ?? '');
    $profileCity = old('city', $zionUser['shipper_city'] ?? $zionUser['city'] ?? '');
    $profileState = old('state', $zionUser['shipper_state'] ?? $zionUser['state'] ?? '');
    $profileZip = old('zip', $zionUser['shipper_zip'] ?? $zionUser['zip'] ?? '');
@endphp

@section('title', 'Account | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Account</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Account</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap dashboard-grid">
        <div class="dashboard-profile-stack">
            <div class="contact-form">
                <div class="login-icon" style="margin-bottom: 22px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="eyebrow">Kay Paolo Session</div>
                <h2 id="dashboardUserName">{{ $zionUser['name'] ?? 'Kay Paolo user' }}</h2>
                <p class="muted-text">Logged in and stored in the Kay Paolo session.</p>
                <dl class="session-list">
                    <div><dt>Role</dt><dd id="dashboardRole">{{ $zionUser['role']['name'] ?? 'User' }}</dd></div>
                    <div><dt>Email</dt><dd id="dashboardEmail">{{ $zionUser['email'] ?? '-' }}</dd></div>
                    <div><dt>Phone</dt><dd id="dashboardPhone">{{ $profilePhone ?: '-' }}</dd></div>
                    <div><dt>Account</dt><dd id="dashboardAccount">{{ $zionUser['account_number'] ?? '-' }}</dd></div>
                </dl>
                <div class="api-inline-result success" id="dashboardAdminAccess" @unless ($isAdminRole) hidden @endunless>
                    Admin access is active for Kay Paolo content and API calls.
                </div>
            </div>

            <div class="contact-form compact-form account-profile-form" id="profileForm">
                <h3>Profile</h3>

                <div class="field">
                    <label for="profileName">Name</label>
                    <input id="profileName" name="name" type="text" value="{{ $profileName }}" readonly>
                </div>
                <div class="form-row-2">
                    <div class="field">
                        <label for="profileEmail">Email</label>
                        <input id="profileEmail" name="email" type="email" value="{{ $profileEmail }}" readonly>
                    </div>
                    <div class="field">
                        <label for="profilePhone">Phone</label>
                        <input id="profilePhone" name="phone" type="text" value="{{ $profilePhone }}" readonly>
                    </div>
                </div>
                <div class="field">
                    <label for="profileAddress">Address</label>
                    <input id="profileAddress" name="address" type="text" value="{{ $profileAddress }}" readonly>
                </div>
                <div class="form-row-3">
                    <div class="field">
                        <label for="profileCity">City</label>
                        <input id="profileCity" name="city" type="text" value="{{ $profileCity }}" readonly>
                    </div>
                    <div class="field">
                        <label for="profileState">State</label>
                        <input id="profileState" name="state" type="text" value="{{ $profileState }}" readonly>
                    </div>
                    <div class="field">
                        <label for="profileZip">Zip</label>
                        <input id="profileZip" name="zip" type="text" value="{{ $profileZip }}" readonly>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-actions">
            <a class="service-card action-card" href="{{ route('quote') }}">
                <span class="num">LIVE API</span>
                <h3>Create Quote</h3>
                <p>Generate rates through Kay Paolo routes backed by live shipping data.</p>
            </a>
            <a class="service-card action-card" href="{{ route('pickup-list') }}">
                <span class="num">PICKUP</span>
                <h3>Pickup List</h3>
                <p>View pending pickups from the Bocicot shipping account. Invoices stay on Invoices &amp; Receipts.</p>
            </a>
            <a class="service-card action-card" href="{{ route('shipment-history') }}">
                <span class="num">BILLING</span>
                <h3>Invoices &amp; Receipts</h3>
                <p>Open labels, receipts, and invoice documents from shipment history.</p>
            </a>
            <a class="service-card action-card" href="{{ route('tracking') }}">
                <span class="num">TRACK</span>
                <h3>Track Shipment</h3>
                <p>Validate tracking numbers inside the Kay Paolo UI.</p>
            </a>
            <a class="service-card action-card" href="{{ route('contact', ['subject' => 'Security / Password']) }}">
                <span class="num">SECURITY</span>
                <h3>Security &amp; Password</h3>
                <p>Request login, password, or account permission support from Kay Paolo.</p>
            </a>
            <a class="service-card action-card" href="{{ route('admin') }}" id="dashboardAdminAction" @unless ($isAdminRole) hidden @endunless>
                <span class="num">ADMIN</span>
                <h3>Manage Content</h3>
                <p>Update Kay Paolo page text and Who We Are pictures.</p>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="contact-form compact-form">
                @csrf
                <button class="btn btn-navy btn-block" type="submit">Logout</button>
            </form>
        </div>
    </div>
</section>
@endsection
