@extends('layouts.site')

@section('title', 'Agent Invoices | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Agent Invoices</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><a href="{{ route('account') }}">Account</a><span class="sep">/</span><span>Agent Invoices</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap">
        <div class="agent-invoices-page" id="agentInvoicesPage">
            <p class="pickup-list-intro">
                These are your Kay Paolo agent invoices from the last 90 days. Download any invoice PDF to review commission, online payments, and amount due.
            </p>

            <div class="api-alert error" id="authNotice">Login first to view agent invoices.</div>
            <div class="api-loader" id="agentInvoicesLoader" hidden>
                <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Loading agent invoices">
            </div>
            <div id="agentInvoicesNotice" class="api-alert error" hidden></div>

            <div class="agent-invoices-table-wrap" id="agentInvoicesResult">
                <div class="pickup-list-empty">
                    <p>Agent invoices will load from your account.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
