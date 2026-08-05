<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shipping Label | Kay Paolo Shipping</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    @php
        $rawLabelNumbers = $documentQuery['id'] ?? $documentQuery['tracking'] ?? $documentQuery['tracking_number'] ?? '';
        $labelNumbers = collect(explode(',', (string) $rawLabelNumbers))
            ->map(fn ($label) => trim($label))
            ->filter()
            ->values()
            ->all();

        if (empty($labelNumbers)) {
            $labelNumbers = ['Pending'];
        }

        $invoiceNumber = trim((string) ($documentQuery['invoice'] ?? $documentQuery['invoice_num'] ?? ''));
        $barcodeValue = $invoiceNumber !== '' ? $invoiceNumber : preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($labelNumbers[0] ?? 'Pending'));
        $deliveryNumber = $invoiceNumber !== ''
            ? 'DLV'.substr(preg_replace('/\D/', '', $invoiceNumber) ?: $invoiceNumber, -6)
            : 'Pending';
    @endphp
    <style>
        * { box-sizing: border-box; }
        body {
            align-items: center;
            background: #f3f4f6;
            color: #000;
            display: flex;
            flex-direction: column;
            font-family: "Inter", Arial, sans-serif;
            gap: 24px;
            justify-content: flex-start;
            margin: 0;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .print-btn-container {
            text-align: right;
            width: 700px;
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
            font-weight: 700;
            gap: 8px;
            padding: 10px 20px;
        }
        .btn-print:hover { background: #0f172a; }
        .receipt-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            box-sizing: border-box;
            padding: 30px;
            width: 700px;
            max-width: 100%;
        }
        .receipt-header {
            border-collapse: collapse;
            margin-bottom: 5px;
            width: 100%;
        }
        .receipt-header td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }
        .header-left { text-align: left; }
        .header-right { text-align: right; }
        .header-left img {
            display: block;
            filter: grayscale(100%) brightness(0);
            height: auto;
            width: 130px;
        }
        .barcode-wrap {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .barcode-wrap svg {
            display: block;
            max-width: 100%;
            height: 56px;
        }
        .awb-no-text,
        .delivery-no-text {
            color: #000;
            font-size: 14px;
            font-weight: 600;
            margin-top: 4px;
        }
        .delivery-no-text { font-size: 13px; }
        .dashed-line {
            border-top: 2px dashed #cbd5e1;
            margin: 15px 0;
            width: 100%;
        }
        .details-table {
            border: 2px solid #000;
            border-collapse: collapse;
            margin-bottom: 25px;
            width: 100%;
        }
        .details-table th,
        .details-table td {
            border: 1px solid #000;
            padding: 12px 15px;
            text-align: left;
            vertical-align: top;
            width: 50%;
        }
        .table-title-due {
            font-size: 28px;
            font-weight: 700;
            padding: 8px !important;
            text-align: center !important;
        }
        .table-title-port {
            font-size: 22px;
            font-weight: 700;
            padding: 8px !important;
            text-align: center !important;
        }
        .section-label {
            color: #000;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .address-text {
            color: #000;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
            white-space: pre-line;
        }
        .sender-box {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
        }
        .weight-dim {
            font-weight: 700;
            margin-top: 15px;
        }
        .huge-awb {
            color: #000;
            font-size: 48px;
            font-weight: 700;
            letter-spacing: 1px;
            margin: 25px 0;
            text-align: center;
            word-break: break-word;
        }
        .lower-barcode-container {
            align-items: flex-end;
            display: flex;
            flex-direction: column;
            margin-bottom: 25px;
        }
        .lower-barcode-container svg {
            display: block;
            height: 48px;
            max-width: 100%;
        }
        .package-info-box {
            border: 2px solid #000;
            min-height: 80px;
            padding: 15px;
        }
        .package-info-title {
            color: #000;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .package-info-content {
            color: #000;
            font-size: 14px;
        }
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
                gap: 0;
            }
            .no-print { display: none !important; }
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
                break-after: page;
                margin: 0 auto;
                padding: 0;
                width: 100%;
            }
            .receipt-container:last-child {
                break-after: auto;
            }
        }
    </style>
</head>
<body>
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

    <div data-package-label-stack>
        @foreach ($labelNumbers as $labelNumber)
            <div class="receipt-container" data-package-label="true" data-shipment-document>
                <table class="receipt-header">
                    <tr>
                        <td class="header-left">
                            <img src="{{ asset('kay-paolo/assets/logo/kay-paolo.svg') }}" alt="Kay Paolo Shipping" width="130" height="65">
                        </td>
                        <td class="header-right">
                            <div class="barcode-wrap">
                                <svg
                                    class="jsbarcode-awb"
                                    data-barcode-role="awb"
                                    data-barcode-value="{{ $barcodeValue }}"
                                    @if ($loop->first) id="receiptA4Barcode" @endif
                                ></svg>
                                <div class="awb-no-text">AWB No. <span data-label-field="trackingLabel" @if ($loop->first) id="receiptA4TrackingNumber" @endif>{{ $labelNumber }}</span></div>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="dashed-line"></div>

                <table class="details-table">
                    <tr>
                        <th class="table-title-due" data-label-field="due" @if ($loop->first) id="labelDueType" @endif>DUE</th>
                        <th class="table-title-port" data-label-field="destination" @if ($loop->first) id="labelDestination" @endif>Destination</th>
                    </tr>
                    <tr>
                        <td>
                            <div class="sender-box">
                                <div>
                                    <div class="section-label">Sender</div>
                                    <div class="address-text" data-label-field="shipper" @if ($loop->first) id="receiptA4Shipper" @endif>Kay Paolo Shipping</div>
                                </div>
                                <div class="address-text weight-dim" data-label-field="weightDim" @if ($loop->first) id="labelWeightDim" @endif>1 lb</div>
                            </div>
                        </td>
                        <td>
                            <div class="section-label">Receiver</div>
                            <div class="address-text" data-label-field="receiver" @if ($loop->first) id="receiptA4Receiver" @endif>Destination customer</div>
                        </td>
                    </tr>
                </table>

                <div class="huge-awb" data-label-field="tracking" @if ($loop->first) id="receiptA4LargeNumber" @endif>{{ $labelNumber }}</div>

                <div class="dashed-line"></div>

                <div class="lower-barcode-container">
                    <svg
                        class="jsbarcode-delivery"
                        data-barcode-role="delivery"
                        data-barcode-value="{{ $deliveryNumber }}"
                        @if ($loop->first) id="labelDeliveryBarcode" @endif
                    ></svg>
                    <div class="delivery-no-text">Delivery No. <span data-label-field="delivery" @if ($loop->first) id="labelDeliveryNumber" @endif>{{ $deliveryNumber }}</span></div>
                </div>

                <div class="package-info-box">
                    <div class="package-info-title">Package Contents:</div>
                    <div class="package-info-content" data-label-field="package" @if ($loop->first) id="receiptA4Package" @endif>Package (1 pcs)</div>
                </div>
            </div>
        @endforeach
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
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js" defer></script>
    <script src="{{ asset('kay-paolo/assets/app.js') }}?v={{ filemtime(public_path('kay-paolo/assets/app.js')) }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function paintBarcodes(root) {
                if (typeof JsBarcode !== 'function') return;
                (root || document).querySelectorAll('svg[data-barcode-value]').forEach(function (svg) {
                    var value = String(svg.getAttribute('data-barcode-value') || '').trim();
                    if (!value || value === 'Pending') return;
                    try {
                        JsBarcode(svg, value, {
                            format: 'CODE128',
                            displayValue: false,
                            margin: 0,
                            height: svg.classList.contains('jsbarcode-delivery') ? 48 : 56,
                            width: 2,
                            background: '#ffffff',
                            lineColor: '#000000'
                        });
                    } catch (error) {}
                });
            }

            paintBarcodes(document);
            window.kayPaoloPaintLabelBarcodes = paintBarcodes;
        });
    </script>
</body>
</html>
