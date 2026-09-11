@extends('layouts.site')

@section('title', 'Pickup List | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Pickup List</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><a href="{{ route('quote') }}">Shipping</a><span class="sep">/</span><span>Pickup List</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap">
        <div class="pickup-list-page" id="pickupListPage">
            <p class="pickup-list-intro">
                These are your active Kay Paolo pickups waiting to be completed. Use the action button to open the pickup completion page, take/upload pictures, and submit the pickup.
            </p>

            <div class="pickup-pending-banner">
                <div>
                    <p class="pickup-pending-label">Pending Pickups</p>
                    <p class="pickup-pending-count" id="pickupPendingCount">0</p>
                </div>
                <div class="pickup-pending-note">Total active pickups waiting to be completed</div>
            </div>

            <div class="controls-panel">
                <div class="controls-left">
                    <span>Show</span>
                    <select id="entriesSelect">
                        <option>100</option>
                        <option>50</option>
                        <option>25</option>
                        <option>10</option>
                    </select>
                    <span>entries</span>

                    <span style="margin-left: 20px">Pickup window</span>
                    <select id="timeSelect">
                        <option>Today</option>
                        <option>This Week</option>
                        <option>Last 30 Days</option>
                        <option selected>All Pickups</option>
                    </select>
                </div>
                <div class="controls-right">
                    <span>Search:</span>
                    <input type="text" id="searchInput" placeholder="Search shipment, client, phone, address…">
                </div>
            </div>

            <div class="history-card-list" id="historyCardList">
                <div class="api-alert error" id="authNotice">Login first to view pickup list.</div>
                <div class="api-loader" id="historyLoader" hidden>
                    <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Loading pickup list">
                </div>
                <div id="historyResult" class="pickup-list-result">
                    <div class="pickup-list-empty">
                        <p>No active pickups are assigned to your states right now.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
