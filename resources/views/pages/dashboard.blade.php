@extends('layouts.site')

@php
    $isAdminRole = in_array((int) ($zionUser['role_id'] ?? 0), [1, 12, 13, 14, 15], true);
    $profileName = old('name', $zionUser['name'] ?? '');
    $profileEmail = old('email', $zionUser['email'] ?? '');
    $profilePhone = old('phone', $zionUser['phone'] ?? $zionUser['mobile'] ?? '');
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
                    <div><dt>Role ID</dt><dd id="dashboardRoleId">{{ $zionUser['role_id'] ?? '-' }}</dd></div>
                    <div><dt>Email</dt><dd id="dashboardEmail">{{ $zionUser['email'] ?? '-' }}</dd></div>
                    <div><dt>Account</dt><dd id="dashboardAccount">{{ $zionUser['account_number'] ?? '-' }}</dd></div>
                </dl>
                <div class="api-inline-result success" id="dashboardAdminAccess" @unless ($isAdminRole) hidden @endunless>
                    Admin access is active for Kay Paolo content and API calls.
                </div>
            </div>

            <form class="contact-form compact-form account-profile-form" method="POST" action="{{ route('account.profile.update') }}" id="profileForm">
                @csrf
                <h3>Profile</h3>

                @if (session('profile_status'))
                    <div class="api-alert success">{{ session('profile_status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="api-alert error">{{ $errors->first() }}</div>
                @endif

                <div class="field">
                    <label for="profileName">Name</label>
                    <input id="profileName" name="name" type="text" value="{{ $profileName }}" required>
                </div>
                <div class="form-row-2">
                    <div class="field">
                        <label for="profileEmail">Email</label>
                        <input id="profileEmail" name="email" type="email" value="{{ $profileEmail }}" required>
                    </div>
                    <div class="field">
                        <label for="profilePhone">Phone</label>
                        <input id="profilePhone" name="phone" type="text" value="{{ $profilePhone }}">
                    </div>
                </div>
                <div class="field">
                    <label for="profileAddress">Address</label>
                    <input id="profileAddress" name="address" type="text" value="{{ $profileAddress }}">
                </div>
                <div class="form-row-3">
                    <div class="field">
                        <label for="profileCity">City</label>
                        <input id="profileCity" name="city" type="text" value="{{ $profileCity }}">
                    </div>
                    <div class="field">
                        <label for="profileState">State</label>
                        <input id="profileState" name="state" type="text" value="{{ $profileState }}">
                    </div>
                    <div class="field">
                        <label for="profileZip">Zip</label>
                        <input id="profileZip" name="zip" type="text" value="{{ $profileZip }}">
                    </div>
                </div>
                <button class="btn btn-gold" type="submit">Save Profile</button>
            </form>
        </div>
        <div class="dashboard-actions">
            <a class="service-card action-card" href="{{ route('quote') }}">
                <span class="num">LIVE API</span>
                <h3>Create Quote</h3>
                <p>Generate rates through Kay Paolo routes backed by live shipping data.</p>
            </a>
            <a class="service-card action-card" href="{{ route('shipment-history') }}">
                <span class="num">PICKUP</span>
                <h3>Pickup List</h3>
                <p>View scheduled pickups and shipment history from the connected shipping account.</p>
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
