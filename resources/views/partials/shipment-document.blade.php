@php
    $documentTitle = $documentTitle ?? 'Invoice';
    $documentKicker = $documentKicker ?? 'Shipment Document';
@endphp

<div class="shipment-doc-actions no-print">
    <button type="button" class="btn btn-navy" onclick="window.print()">Print {{ $documentTitle }}</button>
    <a href="{{ route('receipt.a4') }}" class="btn btn-gold">Open A4 Receipt</a>
    <a href="{{ route('tracking') }}" class="btn btn-outline">Track Shipment</a>
</div>

<article class="shipment-doc" data-shipment-document>
    <header class="shipment-doc-header">
        <div>
            <img src="{{ asset('kay-paolo/assets/logo/kay-paolo.svg') }}" alt="Kay Paolo Shipping" width="150" height="75">
            <p>414 Main St, Asbury Park, NJ 07712<br>info@kaypaoloshipping.com / (732) 898-9303</p>
        </div>
        <div class="shipment-doc-title">
            <span>{{ $documentKicker }}</span>
            <h2>{{ $documentTitle }}</h2>
            <strong id="documentNumber">Pending</strong>
        </div>
    </header>

    <section class="shipment-doc-meta">
        <div><span>Date</span><strong id="documentDate">{{ now()->format('M d, Y') }}</strong></div>
        <div><span>Status</span><strong id="documentStatus">Booked</strong></div>
        <div><span>Payment</span><strong id="documentPaymentType">PAID AT AGENT</strong></div>
        <div><span>Tracking</span><strong id="documentTracking">Pending</strong></div>
    </section>

    <section class="shipment-doc-addresses">
        <div>
            <h3>Shipper</h3>
            <strong id="documentShipperName">Kay Paolo Shipping</strong>
            <p id="documentShipperAddress">414 Main St, Asbury Park, NJ 07712</p>
            <p><strong>Phone:</strong> <span id="documentShipperPhone">Phone pending</span></p>
            <p><strong>Email:</strong> <span id="documentShipperEmail">info@kaypaoloshipping.com</span></p>
        </div>
        <div>
            <h3>Consignee</h3>
            <strong id="documentConsigneeName">Destination Customer</strong>
            <p id="documentConsigneeAddress">Destination address pending</p>
            <p><strong>Phone:</strong> <span id="documentConsigneePhone">Phone pending</span></p>
        </div>
    </section>

    <div class="shipment-doc-table-wrap">
        <table class="shipment-doc-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Weight</th>
                    <th>Dimensions</th>
                    <th>Declared Value</th>
                </tr>
            </thead>
            <tbody id="documentItems">
                <tr>
                    <td></td>
                    <td>1</td>
                    <td>1 lb</td>
                    <td>1 x 1 x 1</td>
                    <td>USD 0.00</td>
                </tr>
            </tbody>
        </table>
    </div>

    <section class="shipment-doc-summary">
        <div class="shipment-doc-note">
            <h3>Shipment Notes</h3>
            <p id="documentNotes">This document is generated from the Kay Paolo shipment flow.</p>
        </div>
        <dl>
            <div><dt>Freight</dt><dd id="documentFreight">USD 0.00</dd></div>
            <div><dt>Insurance</dt><dd id="documentInsurance">USD 0.00</dd></div>
            <div><dt>Home Delivery</dt><dd id="documentHomeDelivery">USD 0.00</dd></div>
            <div><dt>Tax</dt><dd id="documentTax">USD 0.00</dd></div>
            <div class="total"><dt>Total</dt><dd id="documentTotal">USD 0.00</dd></div>
        </dl>
    </section>
</article>
