@extends('layouts.site')

@section('title', 'Tracking Details | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Tracking Details</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Tracking Details</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap">
        <div class="payment-required-banner" id="paymentRequiredBanner" style="display: none">
            <div class="payment-icon">
                <svg viewBox="0 0 24 24"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
            </div>
            <div class="payment-content">
                <h4>PAYMENT REQUIRED</h4>
                <p>There is an outstanding balance of <strong id="paymentAmount">$0.00</strong> that must be paid before this shipment can move forward.</p>
            </div>
            <div class="payment-action">
                <button class="btn btn-gold" id="payNowBtn">Pay Now</button>
            </div>
        </div>

        <div class="tracking-details-card">
            <div class="tracking-details-header">
                <div class="tracking-title-left">
                    <span class="tracking-brand-label">KAY PAOLO TRACKING DETAILS</span>
                    <h3 id="shipmentTitle">Shipment #{{ request('id', 'Pending') }}</h3>
                </div>
                <div class="tracking-title-right">
                    <span class="tracking-arrival-label">EXPECTED ARRIVAL</span>
                    <h3 id="expectedArrivalDate">Pending</h3>
                </div>
            </div>
            <div class="tracking-details-meta">
                <div class="meta-col">
                    <span class="meta-label">CARRIER TRACKING NUMBER</span>
                    <span class="meta-val" id="carrierTrackNum">{{ request('id', '-') }}</span>
                </div>
                <div class="meta-col">
                    <span class="meta-label">ROUTE</span>
                    <span class="meta-val text-uppercase" id="routeText">Pending</span>
                </div>
                <div class="meta-col">
                    <span class="meta-label">SERVICE</span>
                    <span class="meta-val" id="serviceName">Not available</span>
                </div>
            </div>

            <div class="stepper-horizontal" id="stepperHorizontal">
                <div class="progress-line" id="stepProgressLine" style="width: 0%"></div>
                <div class="step-h current" data-step="0">
                    <div class="step-icon-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg></div>
                    <div class="step-details"><b>Shipment Created</b><span id="step0Desc">Completed / Current</span></div>
                </div>
                <div class="step-h" data-step="1">
                    <div class="step-icon-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg></div>
                    <div class="step-details"><b>Shipment Picked Up</b><span id="step1Desc">Pending</span></div>
                </div>
                <div class="step-h" data-step="2">
                    <div class="step-icon-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div>
                    <div class="step-details"><b>In Transit</b><span id="step2Desc">Pending</span></div>
                </div>
                <div class="step-h" data-step="3">
                    <div class="step-icon-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><polygon points="12 22.08 12 12 3 6.92 3 17.08 12 22.08"></polygon><polygon points="12 22.08 12 12 21 6.92 21 17.08 12 22.08"></polygon><polygon points="12 12 3 6.92 12 1.84 21 6.92 12 12"></polygon></svg></div>
                    <div class="step-details"><b>Out for Delivery</b><span id="step3Desc">Pending</span></div>
                </div>
                <div class="step-h" data-step="4">
                    <div class="step-icon-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
                    <div class="step-details"><b>Delivered</b><span id="step4Desc">Pending</span></div>
                </div>
            </div>
        </div>

        <div class="track-another-section">
            <div class="track-another-info">
                <h4>Track Another Shipment</h4>
                <p>Need to check another package? Use the same tracking lookup here without leaving the current shipment details page.</p>
            </div>
            <div class="track-another-form">
                <input type="text" id="trackAnotherInput" placeholder="Enter tracking number" class="mono">
                <button class="btn btn-navy" id="trackAnotherBtn">Track Shipment</button>
            </div>
        </div>

        <div class="tracking-columns-grid">
            <div class="tracking-column-main">
                <div class="current-status-card">
                    <div class="status-card-header">
                        <div class="status-icon-circle" id="statusCardIconCircle">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
                        </div>
                        <div class="status-card-title-wrap">
                            <h3 id="statusCardTitle">Created</h3>
                            <span class="status-card-time" id="statusCardTime">Pending</span>
                        </div>
                    </div>
                    <div class="addresses-row">
                        <div class="address-col">
                            <h5>Shipping Address</h5>
                            <p id="shippingAddressText">Pending</p>
                        </div>
                        <div class="address-col">
                            <h5>Receiver Address</h5>
                            <p id="receiverAddressText">Pending</p>
                        </div>
                    </div>
                </div>

                <div class="timeline-history-card">
                    <h4>Shipment History</h4>
                    <div class="vertical-timeline" id="verticalTimeline">
                        <div class="timeline-event current-event">
                            <div class="timeline-event-date">Pending</div>
                            <div class="timeline-event-content">
                                <h5>Tracking data will appear here</h5>
                                <p>Use the tracking search page or the field above to load tracking details.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tracking-column-side">
                <div class="shipment-card">
                    <div class="shipment-card-header">Shipment Summary</div>
                    <div class="shipment-card-body">
                        <dl class="session-list">
                            <div><dt>Status</dt><dd id="trackingStatusSummary">Pending</dd></div>
                            <div><dt>Invoice</dt><dd id="trackingInvoiceSummary">{{ request('id', '-') }}</dd></div>
                            <div><dt>Weight</dt><dd id="trackingWeightSummary">-</dd></div>
                            <div><dt>Pieces</dt><dd id="trackingPiecesSummary">-</dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
