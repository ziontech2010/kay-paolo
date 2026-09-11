@extends('layouts.site')

@php
    $isPickupList = request()->query('view') === 'pickup';
    $pageTitle = $isPickupList ? 'Pickup List' : 'Shipment History';
@endphp

@section('title', $pageTitle . ' | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>{{ $pageTitle }}</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><a href="{{ route('quote') }}">Shipping</a><span class="sep">/</span><span>{{ $pageTitle }}</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap">
        @if ($isPickupList)
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
        @else
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

                    <span style="margin-left: 20px">Created in</span>
                    <select id="timeSelect">
                        <option selected>Last 30 Days</option>
                        <option>Last 90 Days</option>
                        <option>This Year</option>
                        <option>All Shipments</option>
                    </select>
                </div>
                <div class="controls-right">
                    <span>Search:</span>
                    <input type="text" id="searchInput" placeholder="Search tracking #, name, etc.">
                </div>
            </div>

            <div class="history-layout">
                <div class="history-sidebar">
                    <div class="shipment-card" style="margin-bottom: 0">
                        <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 700; letter-spacing: 0; padding: 12px 20px">Status</div>
                        <div class="shipment-card-body" style="padding: 20px">
                            <div class="filter-list">
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Ready to Ship"> Ready to Ship</span><span class="filter-badge badge-ready" data-history-status-badge="Ready to Ship">0.00%</span></label>
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Picked Up"> Picked Up</span><span class="filter-badge badge-ready" style="background:#e6fcf5;color:#099268" data-history-status-badge="Picked Up">0.00%</span></label>
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="In Transit"> In Transit</span><span class="filter-badge badge-transit" data-history-status-badge="In Transit">0.00%</span></label>
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Customs"> Customs</span><span class="filter-badge badge-customs" data-history-status-badge="Customs">0.00%</span></label>
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Delayed"> Delayed</span><span class="filter-badge badge-delayed" data-history-status-badge="Delayed">0.00%</span></label>
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Available"> Available</span><span class="filter-badge badge-available" data-history-status-badge="Available">0.00%</span></label>
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Not Deliverable"> Not Deliverable</span><span class="filter-badge badge-notdel" data-history-status-badge="Not Deliverable">0.00%</span></label>
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Delivered"> Delivered</span><span class="filter-badge badge-delivered" data-history-status-badge="Delivered">0.00%</span></label>
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Voided"> Voided</span><span class="filter-badge badge-voided" data-history-status-badge="Voided">0.00%</span></label>
                            </div>
                        </div>
                    </div>

                    <div class="shipment-card" style="margin-bottom: 0">
                        <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 700; letter-spacing: 0; padding: 12px 20px">Category</div>
                        <div class="shipment-card-body" style="padding: 20px">
                            <div class="filter-list">
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="category-filter" value="Domestic"> Domestic</span><span class="filter-badge badge-gray" data-history-category-badge="Domestic">0.00%</span></label>
                                <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="category-filter" value="International"> International</span><span class="filter-badge badge-gray" data-history-category-badge="International">0.00%</span></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="history-card-list" id="historyCardList">
                    <div class="api-alert error" id="authNotice">Login first to view shipment history.</div>
                    <div class="api-loader" id="historyLoader" hidden>
                        <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Loading shipment history">
                    </div>
                    <div id="historyResult">
                        <div class="shipment-card" style="margin-bottom: 0">
                            <div class="history-card-main">
                                <div class="history-card-col">
                                    <h4 style="color: var(--navy-800)">Loading</h4>
                                    <span class="status-lbl">Shipment History</span>
                                    <span class="meta-label">Account</span>
                                    <span class="meta-val">Waiting for your login session</span>
                                </div>
                                <div class="history-card-col">
                                    <span class="meta-label">Shipment Date</span>
                                    <span class="meta-val" style="font-weight: 700">-</span>
                                    <span class="meta-label">Delivery Option</span>
                                    <span class="meta-val">-</span>
                                    <span class="meta-label">Description</span>
                                    <span class="meta-val" style="font-weight: 600">Shipment History will load from your account.</span>
                                </div>
                                <div class="history-card-col">
                                    <div class="address-block">
                                        <span class="meta-label">Ship From</span>
                                        <strong>-</strong>
                                        -
                                    </div>
                                </div>
                                <div class="history-card-col">
                                    <div class="address-block">
                                        <span class="meta-label">Ship To</span>
                                        <strong>-</strong>
                                        -
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
