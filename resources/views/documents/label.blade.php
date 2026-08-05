<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>A4 Shipping Label | Kay Paolo Shipping</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Libre+Barcode+39&display=swap" rel="stylesheet">
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

        $invoiceNumber = $documentQuery['invoice'] ?? $documentQuery['invoice_num'] ?? 'Pending';
    @endphp
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        body {
            align-items: center;
            background: #f3f4f6;
            color: #000;
            display: flex;
            flex-direction: column;
            font-family: "Inter", Arial, sans-serif;
            gap: 24px;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
            padding: 24px;
        }
        .print-btn-container {
            left: 20px;
            position: fixed;
            top: 20px;
            z-index: 2;
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
            padding: 11px 20px;
        }
        .page-wrapper {
            align-items: center;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            display: flex;
            height: 297mm;
            justify-content: center;
            overflow: hidden;
            position: relative;
            width: 210mm;
        }
        .page-wrapper + .page-wrapper {
            margin-top: 24px;
        }
        .label-container {
            background: #fff;
            border: 4px solid #000;
            height: 195mm;
            position: absolute;
            transform: rotate(90deg);
            width: 278mm;
        }
        .label-table {
            border-collapse: collapse;
            height: 100%;
            width: 100%;
        }
        .label-table td {
            border-bottom: 3px solid #000;
            color: #000;
            padding: 18px 20px;
            vertical-align: top;
        }
        .label-table tr:last-child td { border-bottom: 0; }
        .header-logo-cell {
            align-items: center;
            border-right: 3px solid #000;
            display: flex;
            justify-content: center;
            padding: 25px 15px !important;
            width: 50%;
        }
        .header-logo-cell img {
            display: block;
            filter: grayscale(100%) brightness(0);
            height: auto;
            width: 170px;
        }
        .header-from-cell {
            font-size: 14px;
            line-height: 1.55;
            width: 50%;
        }
        .inline-table {
            border-collapse: collapse;
            width: 100%;
        }
        .inline-table td {
            border: 0;
            padding: 0 0 5px;
        }
        .inline-label {
            font-weight: 900;
            width: 58px;
        }
        .receiver-cell {
            font-size: 18px;
            line-height: 1.55;
        }
        .receiver-cell strong {
            font-size: 22px;
            font-weight: 900;
        }
        .huge-awb-cell {
            font-size: 58px;
            font-weight: 900;
            letter-spacing: 1px;
            padding: 30px 10px !important;
            text-align: center;
        }
        .due-cell {
            border-right: 3px solid #000;
            font-size: 32px;
            font-weight: 900;
            padding: 10px !important;
            text-align: center;
            vertical-align: middle !important;
            width: 30%;
        }
        .dest-cell {
            font-size: 26px;
            font-weight: 900;
            padding: 10px 20px !important;
            vertical-align: middle !important;
            width: 70%;
        }
        .barcode-cell {
            padding: 34px 20px 18px !important;
            text-align: center;
        }
        .barcode-text {
            display: inline-block;
            font-family: "Libre Barcode 39", "Courier New", monospace;
            font-size: 90px;
            line-height: 1;
        }
        .barcode-label {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 3px;
            margin-top: 8px;
        }
        .package-line {
            font-size: 17px;
            font-weight: 800;
            margin-top: 12px;
            text-align: left;
        }
        @media print {
            html, body {
                background: #fff;
                height: 297mm;
                padding: 0;
                width: 210mm;
            }
            .no-print { display: none !important; }
            .page-wrapper {
                break-after: page;
                box-shadow: none;
                height: 297mm;
                margin: 0;
                width: 210mm;
            }
            .page-wrapper:last-child {
                break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="print-btn-container no-print">
        <button class="btn-print" onclick="window.print()" type="button">Print Label</button>
    </div>

    <div data-package-label-stack>
        @foreach ($labelNumbers as $labelNumber)
            <div class="page-wrapper" data-package-label="true">
                <article class="label-container awb-sheet" data-shipment-document>
                    <table class="label-table">
                        <tr>
                            <td class="header-logo-cell">
                                <img src="{{ asset('kay-paolo/assets/logo/kay-paolo.svg') }}" alt="Kay Paolo Shipping">
                            </td>
                            <td class="header-from-cell">
                                <table class="inline-table">
                                    <tr>
                                        <td class="inline-label">From:</td>
                                        <td><strong>KAY PAOLO SHIPPING</strong></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td data-label-field="shipper" @if ($loop->first) id="receiptA4Shipper" @endif>Kay Paolo Shipping</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td data-label-field="package" @if ($loop->first) id="receiptA4Package" @endif>1 package</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="receiver-cell">
                                <table class="inline-table">
                                    <tr>
                                        <td class="inline-label">To:</td>
                                        <td><strong data-label-field="receiver" @if ($loop->first) id="receiptA4Receiver" @endif>Destination customer</strong></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="huge-awb-cell" data-label-field="tracking" @if ($loop->first) id="receiptA4LargeNumber" @endif>{{ $labelNumber }}</td>
                        </tr>
                        <tr>
                            <td class="due-cell" data-label-field="due" @if ($loop->first) id="labelDueType" @endif>DUE</td>
                            <td class="dest-cell" data-label-field="destination" @if ($loop->first) id="labelDestination" @endif>Destination</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="barcode-cell">
                                <div class="barcode-text" data-label-field="barcode" @if ($loop->first) id="receiptA4Barcode" @endif>*{{ $labelNumber }}*</div>
                                <div class="barcode-label" data-label-field="trackingLabel" @if ($loop->first) id="receiptA4TrackingNumber" @endif>{{ $labelNumber }}</div>
                                <div class="package-line">Delivery: <span data-label-field="delivery" @if ($loop->first) id="labelDeliveryNumber" @endif>{{ $invoiceNumber }}</span></div>
                            </td>
                        </tr>
                    </table>
                </article>
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
    <script src="{{ asset('kay-paolo/assets/app.js') }}?v={{ filemtime(public_path('kay-paolo/assets/app.js')) }}" defer></script>
</body>
</html>
