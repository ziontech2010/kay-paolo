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
<section>
    <div class="wrap login-wrap">
        <div class="login-card">
            <div class="login-icon"></div>
            <h2>Zion Account Login</h2>
            <p class="sub">Sign in with an active Zion Shipping user. Kay Paolo will keep the returned token and role in this session.</p>

            @if ($errors->any())
                <div class="api-alert error">{{ $errors->first() }}</div>
            @endif

            <form class="login-form" method="POST" action="{{ route('login.submit') }}">
                @csrf
                <div class="field">
                    <label for="email">Email or Phone</label>
                    <input type="text" id="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
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
                <button type="submit" class="btn btn-navy btn-block">Login</button>
            </form>

            <p class="login-foot">Kay Paolo does not change Zion users. Authentication is delegated to dev Zion Shipping.</p>
        </div>
    </div>
</section>
@endsection
