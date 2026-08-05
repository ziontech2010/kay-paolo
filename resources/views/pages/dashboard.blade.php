@extends('layouts.site')

@php
    $isAdminRole = in_array((int) ($zionUser['role_id'] ?? 0), [1, 12, 13, 14, 15], true);
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
        <div class="dashboard-actions">
            <a class="service-card action-card" href="{{ route('quote') }}">
                <span class="num">LIVE API</span>
                <h3>Create Quote</h3>
                <p>Generate rates through Kay Paolo routes backed by live shipping data.</p>
            </a>
            <a class="service-card action-card" href="{{ route('tracking') }}">
                <span class="num">TRACK</span>
                <h3>Track Shipment</h3>
                <p>Validate tracking numbers inside the Kay Paolo UI.</p>
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
