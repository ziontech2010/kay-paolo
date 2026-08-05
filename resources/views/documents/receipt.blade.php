<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shipment Receipt | Kay Paolo Shipping</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            align-items: center;
            background: #f3f4f6;
            color: #1a202c;
            display: flex;
            font-family: "Inter", Arial, sans-serif;
            justify-content: center;
            margin: 0;
            padding: 40px 20px;
        }
        .document-shell {
            align-items: center;
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .print-btn-container {
            margin-bottom: 20px;
            text-align: right;
            width: 850px;
        }
        .btn-print {
            align-items: center;
            background: #1e293b;
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            gap: 8px;
            padding: 10px 20px;
        }
        .invoice-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 40px;
            width: 850px;
        }
        .invoice-header {
            border-collapse: collapse;
            margin-bottom: 30px;
            width: 100%;
        }
        .invoice-header td {
            border: 0;
            vertical-align: middle;
        }
        .header-logo img {
            display: block;
            height: auto;
            width: 120px;
        }
        .header-title {
            color: #111827;
            font-size: 32px;
            font-weight: 900;
            letter-spacing: 1px;
            text-align: right;
            text-transform: uppercase;
        }
        .header-title span {
            color: #4b5563;
            display: block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        .info-table {
            border-collapse: collapse;
            margin-bottom: 30px;
            width: 100%;
        }
        .info-table td {
            border: 0;
            box-sizing: border-box;
            font-size: 13px;
            line-height: 1.7;
            padding: 0 25px;
            vertical-align: top;
            width: 33.33%;
        }
        .info-table td:first-child { padding-left: 0; }
        .info-table td:last-child { padding-right: 0; }
        .info-label {
            color: #4b5563;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .shipment-details {
            color: #1f2937;
            font-size: 13px;
        }
        .shipment-details strong {
            color: #111827;
            font-size: 15px;
        }
        .items-table {
            border: 1px solid #cbd5e1;
            border-collapse: collapse;
            margin-bottom: 25px;
            width: 100%;
        }
        .items-table th {
            border-bottom: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            color: #4b5563;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            text-align: left;
            text-transform: uppercase;
        }
        .items-table td {
            border-bottom: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            color: #1f2937;
            font-size: 14px;
            padding: 16px 10px;
            vertical-align: top;
        }
        .items-table th:last-child,
        .items-table td:last-child {
            border-right: 0;
        }
        .legal-weight-table {
            border-collapse: collapse;
            margin-bottom: 25px;
            width: 100%;
        }
        .legal-weight-table td {
            border: 0;
            vertical-align: top;
        }
        .legal-text-col {
            padding-right: 30px;
            width: 65%;
        }
        .weight-col {
            border-left: 1px solid #e5e7eb !important;
            padding-left: 20px;
            width: 35%;
        }
        .legal-text {
            color: #4b5563;
            font-size: 8.5px;
            line-height: 1.4;
            text-align: justify;
        }
        .signature-area {
            font-size: 12px;
            font-weight: 700;
            margin-top: 15px;
        }
        .special-notice {
            color: #111827;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.4;
            margin-top: 12px;
        }
        .weight-table {
            border-collapse: collapse;
            width: 100%;
        }
        .weight-table td {
            border: 0;
            font-size: 14px;
            padding: 8px 0;
        }
        .weight-val {
            color: #111827;
            font-weight: 800;
            text-align: right;
        }
        .pricing-summary-table {
            border-bottom: 1px solid #cbd5e1;
            border-collapse: collapse;
            border-top: 1px solid #cbd5e1;
            margin: 20px 0 30px;
            width: 100%;
        }
        .pricing-summary-table td {
            padding: 15px 10px;
            text-align: center;
            vertical-align: middle;
        }
        .price-box-label,
        .total-box-label {
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .price-box-value {
            color: #111827;
            font-size: 20px;
            font-weight: 800;
        }
        .price-operator {
            color: #9ca3af;
            font-size: 24px;
            font-weight: 400;
            width: 40px;
        }
        .total-box {
            text-align: right !important;
        }
        .total-box-value {
            color: #111827;
            font-size: 32px;
            font-weight: 900;
        }
        .invoice-notes {
            color: #4b5563;
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .thank-you-footer {
            border-top: 1px solid #cbd5e1;
            padding-top: 20px;
            text-align: center;
        }
        .thank-you-title {
            color: #111827;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .footer-links {
            align-items: center;
            color: #4b5563;
            display: flex;
            font-size: 12px;
            gap: 25px;
            justify-content: center;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print { display: none !important; }
            .invoice-container {
                border-radius: 0;
                box-shadow: none;
                padding: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="document-shell">
        <div class="print-btn-container no-print">
            <button class="btn-print" onclick="window.print()" type="button">Print Receipt</button>
        </div>

        <article class="invoice-container" data-shipment-document>
            <table class="invoice-header">
                <tr>
                    <td class="header-logo">
                        <img src="{{ asset('kay-paolo/assets/logo/kay-paolo.svg') }}" alt="Kay Paolo Shipping">
                    </td>
                    <td class="header-title">
                        Receipt
                        <span id="documentNumber">{{ $documentQuery['invoice'] ?? $documentQuery['id'] ?? 'Pending' }}</span>
                    </td>
                </tr>
            </table>

            <table class="info-table">
                <tr>
                    <td>
                        <div class="info-label">From</div>
                        <div class="shipment-details">
                            <strong id="documentShipperName">Kay Paolo Shipping</strong><br>
                            <span id="documentShipperAddress">414 Main St, Asbury Park, NJ 07712</span><br>
                            <span id="documentShipperContact">info@kaypaoloshipping.com</span>
                        </div>
                    </td>
                    <td>
                        <div class="info-label">To</div>
                        <div class="shipment-details">
                            <strong id="documentConsigneeName">Destination Customer</strong><br>
                            <span id="documentConsigneeAddress">Destination address pending</span><br>
                            <span id="documentConsigneeContact">Phone pending</span>
                        </div>
                    </td>
                    <td>
                        <div class="info-label">Shipment Details</div>
                        <div class="shipment-details">
                            <strong id="documentTracking">{{ $documentQuery['id'] ?? 'Pending' }}</strong><br>
                            Date: <span id="documentDate">{{ now()->format('M d, Y') }}</span><br>
                            Status: <span id="documentStatus">Booked</span><br>
                            Payment: <span id="documentPaymentType">PAID AT AGENT</span>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="items-table">
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

            <table class="legal-weight-table">
                <tr>
                    <td class="legal-text-col">
                        <div class="legal-text">
                            Kay Paolo Shipping acts as carrier/agent for the shipment described on this receipt. Customer confirms that the package contents, values, and consignee information provided are accurate. Claims, delivery commitments, customs clearance, and restricted items are subject to the applicable carrier and destination-country rules.
                        </div>
                        <div class="signature-area">Customer Signature: ______________________________</div>
                        <div class="special-notice" id="documentNotes">Thank you for shipping with Kay Paolo Shipping.</div>
                    </td>
                    <td class="weight-col">
                        <table class="weight-table">
                            <tr><td>Package Count</td><td class="weight-val" id="receiptPackageCount">1</td></tr>
                            <tr><td>Total Weight</td><td class="weight-val" id="receiptTotalWeight">1 lb</td></tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="pricing-summary-table">
                <tr>
                    <td>
                        <div class="price-box-label">Freight</div>
                        <div class="price-box-value" id="documentFreight">USD 0.00</div>
                    </td>
                    <td class="price-operator">+</td>
                    <td>
                        <div class="price-box-label">Insurance</div>
                        <div class="price-box-value" id="documentInsurance">USD 0.00</div>
                    </td>
                    <td class="price-operator">+</td>
                    <td>
                        <div class="price-box-label">Tax</div>
                        <div class="price-box-value" id="documentTax">USD 0.00</div>
                    </td>
                    <td class="price-operator">=</td>
                    <td class="total-box">
                        <div class="total-box-label">Total</div>
                        <div class="total-box-value" id="documentTotal">USD 0.00</div>
                    </td>
                </tr>
            </table>

            <div class="invoice-notes">
                Home delivery: <strong id="documentHomeDelivery">USD 0.00</strong>
            </div>

            <footer class="thank-you-footer">
                <div class="thank-you-title">Thank you for choosing Kay Paolo Shipping</div>
                <div class="footer-links">
                    <span>info@kaypaoloshipping.com</span>
                    <span>(732) 898-9303</span>
                    <span>414 Main St, Asbury Park, NJ 07712</span>
                </div>
            </footer>
        </article>
    </div>

    <script>
        window.KayPaolo = {
            authenticated: @json((bool) session('zion.access_token')),
            sessionToken: @json(session('zion.access_token')),
            sessionUser: @json(session('zion.user', [])),
            routes: {},
            assets: {
                kayPaoloLogo: @json(asset('kay-paolo/assets/logo/kay-paolo.svg'))
            }
        };
    </script>
    <script src="{{ asset('kay-paolo/assets/app.js') }}?v={{ filemtime(public_path('kay-paolo/assets/app.js')) }}" defer></script>
</body>
</html>
