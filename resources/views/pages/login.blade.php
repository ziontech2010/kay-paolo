@extends('layouts.site')

@section('title', 'Login | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Login</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Login</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap login-wrap">
        <div class="login-card">
            <div class="login-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <h2>Agent &amp; Client Login</h2>
            <p class="sub">Sign in with an active Kay Paolo-enabled account to manage quotes, shipments, and tracking.</p>

            @if ($errors->any())
                <div class="api-alert error">{{ $errors->first() }}</div>
            @endif
            @if (request('login_error'))
                <div class="api-alert error">{{ request('login_error') }}</div>
            @endif

            <div class="api-alert error" id="loginApiError" hidden></div>
            <div class="api-alert success" id="loginApiSuccess" hidden></div>

            <form class="login-form" method="POST" action="{{ route('login.submit') }}" id="loginForm" data-api-login data-api-endpoint="{{ route('zion-api.login') }}">
                @csrf
                <input type="hidden" name="redirect" value="{{ request('redirect') }}">
                <div class="field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" required autocomplete="username">
                </div>
                <div class="field" style="margin-bottom: 0">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label for="role_id">Role Filter Optional</label>
                    <select id="role_id" name="role_id">
                        <option value="">Auto detect role</option>
                        <option value="2" @selected(old('role_id') === '2')>Client</option>
                        <option value="8" @selected(old('role_id') === '8')>Agent</option>
                        <option value="1" @selected(old('role_id') === '1')>Admin</option>
                        <option value="16" @selected(old('role_id') === '16')>Driver</option>
                    </select>
                </div>
                <div class="login-row-between">
                    <label style="display: flex; align-items: center; gap: 8px"><input type="checkbox" style="width: auto"> Remember me</label>
                    <a href="{{ route('contact') }}">Need help?</a>
                </div>
                <button type="submit" class="btn btn-navy btn-block">Login</button>
            </form>

            <p class="login-foot">Do not have an account? <a href="{{ route('contact') }}">Contact us to get set up</a></p>
        </div>
    </div>
</section>
@endsection
