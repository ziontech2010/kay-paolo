<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label {{ $trackingDisplay }}</title>
    <style>
        @page { margin: 10px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #000;
            font-size: 15px;
            margin: 0;
            padding: 0;
        }
        .page {
            page-break-after: always;
            width: 100%;
            border: 3px solid #000;
        }
        .page:last-child { page-break-after: auto; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; padding: 8px 10px; }
        .row-border { border-bottom: 3px solid #000; }
        .col-border { border-right: 3px solid #000; }
        .logo { width: 120px; height: auto; }
        .from-label {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .from-name { font-size: 15px; font-weight: 700; line-height: 1.25; }
        .from-meta { font-size: 14px; line-height: 1.3; margin-top: 2px; }
        .weight { font-size: 15px; font-weight: 700; margin-top: 8px; }
        .to-label {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .to-name { font-size: 26px; font-weight: 700; line-height: 1.15; }
        .to-addr { font-size: 18px; font-weight: 700; line-height: 1.25; margin-top: 4px; white-space: pre-line; }
        .to-phone { font-size: 18px; font-weight: 700; margin-top: 6px; }
        .awb {
            font-size: 48px;
            font-weight: 700;
            text-align: center;
            padding: 8px 4px !important;
            letter-spacing: 0.5px;
            line-height: 1.05;
            word-break: break-word;
        }
        .status-cell {
            width: 34%;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            vertical-align: middle !important;
            padding: 10px 6px !important;
        }
        .dest-cell {
            width: 66%;
            font-size: 26px;
            font-weight: 700;
            text-align: center;
            vertical-align: middle !important;
            padding: 10px 6px !important;
            line-height: 1.15;
        }
        .scan-wrap {
            text-align: center;
            padding: 8px 6px 4px !important;
        }
        .scan-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .scan-barcode {
            width: 94%;
            height: 90px;
            display: block;
            margin: 0 auto;
        }
        .scan-awb {
            font-size: 15px;
            font-weight: 700;
            margin-top: 4px;
        }
        .pkg-title { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
        .pkg-text { font-size: 15px; font-weight: 700; line-height: 1.25; }
        .footer-meta {
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            padding: 6px 8px !important;
        }
    </style>
</head>
<body>
@foreach ($labels as $labelNumber)
    <div class="page">
        <table>
            <tr>
                <td class="row-border col-border" style="width: 42%; text-align: center; vertical-align: middle !important; padding: 10px;">
                    @if (is_file($logoPath))
                        <img class="logo" src="{{ $logoPath }}" alt="Kay Paolo Shipping">
                    @else
                        <strong style="font-size: 16px">KAY PAOLO</strong>
                    @endif
                </td>
                <td class="row-border" style="width: 58%">
                    <div class="from-label">From</div>
                    <div class="from-name">{{ $shipperName }}</div>
                    <div class="from-meta">{{ $shipperPhone }}</div>
                    <div class="from-meta" style="white-space: pre-line">{{ $shipperAddress }}</div>
                    <div class="weight">{{ $weightDim }}</div>
                </td>
            </tr>
            <tr>
                <td class="row-border" colspan="2">
                    <div class="to-label">To</div>
                    <div class="to-name">{{ $consigneeName }}</div>
                    <div class="to-addr">{{ $consigneeAddress }}</div>
                    <div class="to-phone">{{ $consigneePhone }}</div>
                </td>
            </tr>
            <tr>
                <td class="row-border awb" colspan="2">{{ $labelNumber }}</td>
            </tr>
            <tr>
                <td class="row-border col-border status-cell">{{ $chargeStatus }}</td>
                <td class="row-border dest-cell">{{ $destination }}</td>
            </tr>
            <tr>
                <td class="row-border scan-wrap" colspan="2">
                    <div class="scan-title">Scan Code</div>
                    @if (($scanBarcodeUri ?? $barcodeUri) !== '')
                        <img class="scan-barcode" src="{{ $scanBarcodeUri ?? $barcodeUri }}" alt="scan barcode">
                    @endif
                    <div class="scan-awb">{{ $barcodeValue }} &nbsp;|&nbsp; {{ $labelNumber }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 8px 10px">
                    <div class="pkg-title">Package Contents</div>
                    <div class="pkg-text">{{ $packageText }}</div>
                </td>
            </tr>
            <tr>
                <td class="footer-meta" colspan="2" style="border-top: 3px solid #000">
                    Invoice {{ $invoice }} &nbsp;|&nbsp; Delivery {{ $deliveryNumber }}
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
