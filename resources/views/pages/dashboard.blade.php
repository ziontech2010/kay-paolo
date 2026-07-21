@extends('layouts.site')

@section('title', 'Dashboard | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Dashboard</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Dashboard</span></div>
    </div>
</div>
@endsection

@section('content')
<section>
    <div class="wrap dashboard-grid">
        <div class="contact-form">
            <div class="eyebrow">Zion Session</div>
            <h2 id="dashboardUserName">{{ $zionUser['name'] ?? 'Zion user' }}</h2>
            <p class="muted-text">Logged in through dev Zion Shipping and stored in Kay Paolo session.</p>
            <dl class="session-list">
                <div><dt>Role</dt><dd id="dashboardRole">{{ $zionUser['role']['name'] ?? 'User' }}</dd></div>
                <div><dt>Role ID</dt><dd id="dashboardRoleId">{{ $zionUser['role_id'] ?? '-' }}</dd></div>
                <div><dt>Email</dt><dd id="dashboardEmail">{{ $zionUser['email'] ?? '-' }}</dd></div>
                <div><dt>Account</dt><dd id="dashboardAccount">{{ $zionUser['account_number'] ?? '-' }}</dd></div>
            </dl>
        </div>
        <div class="dashboard-actions">
            <a class="service-card action-card" href="{{ route('quote') }}"><span class="num">Live API</span><h3>Create Quote</h3><p>Generate rates using `/api/kay-paolo/get-quote-result` on dev Zion.</p></a>
            <a class="service-card action-card" href="{{ route('tracking') }}"><span class="num">Public API</span><h3>Track Shipment</h3><p>Validate tracking numbers through Zion without touching Zion UI flows.</p></a>
            <form method="POST" action="{{ route('logout') }}" class="contact-form compact-form">
                @csrf
                <button class="btn btn-navy btn-block" type="submit">Logout</button>
            </form>
        </div>
    </div>
</section>
@endsection
