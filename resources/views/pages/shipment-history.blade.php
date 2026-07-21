@extends('layouts.site')

@section('title', 'Shipment History | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Shipment History</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Shipment History</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap">
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
                    <option>Last 30 Days</option>
                    <option>Last 90 Days</option>
                    <option>This Year</option>
                    <option>All Time</option>
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
                            <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Ready to Ship"> Ready to Ship</span><span class="filter-badge badge-ready">Ready</span></label>
                            <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Picked Up"> Picked Up</span><span class="filter-badge badge-ready" style="background:#e6fcf5;color:#099268">Picked</span></label>
                            <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="In Transit"> In Transit</span><span class="filter-badge badge-transit">Transit</span></label>
                            <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Customs"> Customs</span><span class="filter-badge badge-customs">Customs</span></label>
                            <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="status-filter" value="Delivered"> Delivered</span><span class="filter-badge badge-delivered">Done</span></label>
                        </div>
                    </div>
                </div>

                <div class="shipment-card" style="margin-bottom: 0">
                    <div class="shipment-card-header" style="text-transform: none; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 700; letter-spacing: 0; padding: 12px 20px">Category</div>
                    <div class="shipment-card-body" style="padding: 20px">
                        <div class="filter-list">
                            <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="category-filter" value="Domestic"> Domestic</span><span class="filter-badge badge-gray">Local</span></label>
                            <label class="filter-item"><span class="filter-item-label"><input type="checkbox" class="category-filter" value="International"> International</span><span class="filter-badge badge-gray">Global</span></label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="history-card-list" id="historyCardList">
                <div class="api-alert error" id="authNotice">Login first to view Zion shipment history.</div>
                <div class="api-loader" id="historyLoader" hidden>
                    <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Loading shipment history">
                </div>
                <div id="historyResult">
                    <div class="shipment-card" data-status="Ready to Ship" data-category="International" data-search-pool="HTS323312 Kay Paolo Haiti" style="margin-bottom: 0">
                        <div class="history-card-main">
                            <div class="history-card-col">
                                <h4 style="color: var(--navy-800)">HTS323312</h4>
                                <span class="status-lbl">Ready to Ship</span>
                                <span class="meta-label">Created By</span>
                                <span class="meta-val">Kay Paolo Shipping</span>
                            </div>
                            <div class="history-card-col">
                                <span class="meta-label">Shipment Date</span>
                                <span class="meta-val" style="font-weight: 700">Jul 13, 2026</span>
                                <span class="meta-label">Delivery Option</span>
                                <span class="meta-val">ZION Economical Air</span>
                                <span class="meta-label">Description</span>
                                <span class="meta-val" style="font-weight: 600">General merchandise</span>
                            </div>
                            <div class="history-card-col">
                                <div class="address-block">
                                    <span class="meta-label">Ship From</span>
                                    <strong>Kay Paolo Shipping</strong>
                                    414 Main St, Asbury Park, NJ 07712
                                </div>
                            </div>
                            <div class="history-card-col">
                                <div class="address-block">
                                    <span class="meta-label">Ship To</span>
                                    <strong>Customer</strong>
                                    Destination address
                                </div>
                                <a class="quick-view-link" href="{{ route('tracking.detail', ['id' => 'HTS323312']) }}">Quick View</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
