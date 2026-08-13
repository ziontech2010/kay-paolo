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
    @php
        $invoiceNumber = $documentQuery['invoice'] ?? $documentQuery['invoice_num'] ?? 'Pending';
        $trackingNumber = $documentQuery['id'] ?? $documentQuery['tracking'] ?? $invoiceNumber;
    @endphp
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
            max-width: 100%;
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
            font-weight: 600;
            gap: 8px;
            padding: 10px 20px;
        }
        .btn-print:hover { background: #0f172a; }
        .invoice-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 40px;
            width: 850px;
            max-width: 100%;
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
            font-weight: 800;
            letter-spacing: 1px;
            text-align: right;
            text-transform: uppercase;
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
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .shipment-details {
            color: #1f2937;
            font-size: 13px;
            white-space: pre-line;
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
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            text-align: left;
            text-transform: uppercase;
        }
        .items-table th:last-child,
        .items-table td:last-child {
            border-right: 0;
        }
        .items-table td {
            border-bottom: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            color: #1f2937;
            font-size: 14px;
            padding: 16px 10px;
            vertical-align: top;
        }
        .legal-text {
            color: #4b5563;
            font-size: 8.5px;
            line-height: 1.4;
            text-align: justify;
        }
        .legal-item { margin-bottom: 6px; }
        .signature-area {
            font-size: 12px;
            font-weight: 600;
            margin-top: 15px;
        }
        .special-notice {
            color: #111827;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
            margin-top: 12px;
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
            vertical-align: middle;
        }
        .price-box { text-align: center; }
        .price-box-label,
        .total-box-label {
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .price-box-value {
            color: #111827;
            font-size: 20px;
            font-weight: 700;
        }
        .price-operator {
            color: #9ca3af;
            font-size: 24px;
            font-weight: 400;
            text-align: center;
            width: 40px;
        }
        .total-box {
            padding-right: 20px;
            text-align: right;
        }
        .total-box-value {
            color: #111827;
            font-size: 32px;
            font-weight: 800;
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
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .footer-links {
            align-items: center;
            color: #4b5563;
            display: flex;
            flex-wrap: wrap;
            font-size: 12px;
            gap: 25px;
            justify-content: center;
        }
        .footer-link-item {
            align-items: center;
            color: inherit;
            display: inline-flex;
            gap: 6px;
            text-decoration: none;
        }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
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
            <button class="btn-print" onclick="window.print()" type="button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Print / Save PDF
            </button>
        </div>

        <article class="invoice-container" data-shipment-document>
            <table class="invoice-header">
                <tr>
                    <td class="header-logo">
                        <img src="{{ asset('kay-paolo/assets/logo/kay-paolo.svg') }}" alt="Kay Paolo Shipping" width="120">
                    </td>
                    <td class="header-title">Invoice</td>
                </tr>
            </table>

            <table class="info-table">
                <tr>
                    <td>
                        <div class="shipment-details">
                            <strong id="documentServiceSummary">Shipping Service</strong><br><br>
                            <strong>Shipment no:</strong> <span id="documentTracking">{{ $trackingNumber }}</span><br>
                            <strong>Account No:</strong> <span id="documentAccountNumber">-</span><br>
                            <strong>Created:</strong> <span id="documentDate">{{ now()->format('M d, Y h:i a') }}</span><br>
                            <strong>Delivery Date:</strong> <span id="documentDeliveryDate">Pending</span>
                        </div>
                    </td>
                    <td>
                        <div class="info-label">Shipper:</div>
                        <div class="shipment-details">
                            <strong id="documentShipperName">Kay Paolo Shipping</strong><br>
                            <span id="documentShipperAddress">414 Main St, Asbury Park, NJ 07712</span><br>
                            <strong>Phone:</strong> <span id="documentShipperPhone">Phone pending</span><br>
                            <strong>Email:</strong> <span id="documentShipperEmail">info@kaypaoloshipping.com</span>
                        </div>
                    </td>
                    <td>
                        <div class="info-label">Consignee:</div>
                        <div class="shipment-details">
                            <strong id="documentConsigneeName">Destination Customer</strong><br>
                            <span id="documentConsigneeAddress">Destination address pending</span><br>
                            <strong>Phone:</strong> <span id="documentConsigneePhone">Phone pending</span>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 10%">No Pieces</th>
                        <th style="width: 58%">Package Description</th>
                        <th style="width: 10%; text-align: right">Weight</th>
                        <th style="width: 10%; text-align: right">Volume</th>
                        <th style="width: 12%; text-align: right">Dimension</th>
                    </tr>
                </thead>
                <tbody id="documentItems">
                    <tr>
                        <td>01</td>
                        <td>Package</td>
                        <td style="text-align: right">1 lbs</td>
                        <td style="text-align: right">1.00</td>
                        <td style="text-align: right">1 x 1 x 1</td>
                    </tr>
                    <tr>
                        <td style="border-bottom: none"></td>
                        <td style="border-bottom: none; padding: 15px 10px; vertical-align: top">
                            <div class="legal-text">
                                <div class="legal-item">
                                    I ____________________, hereby certify that this cargo does not contain any illegal, unauthorized,
                                    explosives, incendiaries, or hazardous materials. I consent to a search of this cargo. I am aware that:
                                </div>
                                <div class="legal-item">
                                    (1) Cargo containing hazardous materials (dangerous goods) for transportation by aircraft must be
                                    offered in accordance with Federal Hazardous Materials Regulations (49 CFR parts 171 through 180).
                                </div>
                                <div class="legal-item">
                                    (2) A violation can result in five years' imprisonment and penalties of $250,000 or more (49 U.S.C. 5124).
                                    Failure to comply with the above will result in further disciplinary actions. Kay Paolo Shipping will not be held responsible.
                                </div>
                                <div class="legal-item">
                                    (4) I understand that Kay Paolo Shipping will only refund the amount that was declared for the items
                                    in my package if items are lost or damaged. Undeclared items may subject to additional fees
                                    depending on customs and duties regulations. Any additional fees from undeclared items will be
                                    charged to the shipper. If no declared value was given at the time that shipment was made, Kay Paolo
                                    Shipping will not provide no form of refund or credit.
                                </div>
                                <div class="legal-item">
                                    (5) Client has certified that all items in the packages have been declared. Items that were not
                                    listed or declared will not be considered for refund or credit. Undeclared items will subject to a
                                    charge back and this fee will have to pay before the delivery. If any items are lost or damaged,
                                    they must be reported within 24 hours from the delivery or pick-up time. Failure to do so will
                                    result in claim being denied. In this case, no refund or credit will be provided.
                                </div>
                                <div class="legal-item">
                                    (6) I also certify that all information I provided is accurate and complete.
                                </div>
                                <div class="legal-item">
                                    (7) THERE MAY BE AN ADDITIONAL CUSTOMS AND DUTIES FEE. KAY PAOLO SHIPPING CANNOT GIVE ANY ESTIMATE
                                    ABOUT THIS CHARGE BECAUSE IT IS UNDER HAITI CUSTOMS AUTHORITIES CONTROL.
                                </div>
                                <div class="signature-area">Shipper’s signature : ___________________________</div>
                                <div class="special-notice" id="documentNotes">
                                    Special Notice: Due to current situational instabilities and territorial security issues in Haiti,
                                    we cannot guarantee any delivery date. The provided date is only an estimated timeframe.
                                </div>
                            </div>
                        </td>
                        <td colspan="2" style="border-bottom: none; color: #4b5563; font-weight: 600; padding: 15px 10px; vertical-align: top">
                            Total Weight:
                        </td>
                        <td id="receiptTotalWeight" style="border-bottom: none; color: #111827; font-weight: 700; padding: 15px 10px; text-align: right; vertical-align: top">
                            1 lbs
                        </td>
                    </tr>
                </tbody>
            </table>

            <table class="pricing-summary-table">
                <tr>
                    <td style="width: 25%">
                        <div class="price-box">
                            <div class="price-box-label">Subtotal</div>
                            <div class="price-box-value" id="documentFreight">$0.00</div>
                        </div>
                    </td>
                    <td class="price-operator" style="width: 5%">+</td>
                    <td style="width: 25%">
                        <div class="price-box">
                            <div class="price-box-label">Tax</div>
                            <div class="price-box-value" id="documentTax">$0.00</div>
                        </div>
                    </td>
                    <td style="width: 20%">&nbsp;</td>
                    <td class="total-box" style="width: 25%">
                        <div class="total-box-label">Total</div>
                        <div class="total-box-value" id="documentTotal">$0.00</div>
                    </td>
                </tr>
            </table>

            <div class="invoice-notes">
                * Payment Status: <span id="documentPaymentStatus">Payment is Due</span><br>
                * Total Value: <span id="documentDeclaredValue">$0.00</span><br>
                * If you have any questions concerning this invoice, contact (732) 898-9303, info@kaypaoloshipping.com
            </div>

            <footer class="thank-you-footer">
                <div class="thank-you-title">Thank You For Your Business</div>
                <div class="footer-links">
                    <a href="https://kaypaoloshipping.com" class="footer-link-item" target="_blank" rel="noopener">kaypaoloshipping.com</a>
                    <span class="footer-link-item">(732) 898-9303</span>
                    <a href="mailto:info@kaypaoloshipping.com" class="footer-link-item">info@kaypaoloshipping.com</a>
                </div>
            </footer>

            {{-- Hidden fields kept for shared document JS compatibility --}}
            <span id="documentNumber" hidden>{{ $invoiceNumber }}</span>
            <span id="documentStatus" hidden>Booked</span>
            <span id="documentPaymentType" hidden>PAID AT AGENT</span>
            <span id="documentInsurance" hidden>$0.00</span>
            <span id="documentHomeDelivery" hidden>$0.00</span>
            <span id="receiptPackageCount" hidden>1</span>
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
