<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label {{ $trackingDisplay }}</title>
    <style>
        @page { margin: 10px; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #000;
            font-size: 13px;
        }
        .page {
            width: 100%;
            page-break-inside: avoid;
            page-break-after: always;
        }
        .page:last-child { page-break-after: auto; }
        .frame {
            width: 100%;
            border-collapse: collapse;
            border: 3px solid #000;
            table-layout: fixed;
        }
        .frame td {
            vertical-align: top;
            padding: 5px 7px;
            border: 0;
        }
        .row-border { border-bottom: 2px solid #000 !important; }
        .col-border { border-right: 2px solid #000 !important; }
        .logo { width: 84px; height: auto; }
        .from-label, .to-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .from-name { font-size: 13px; font-weight: 700; line-height: 1.15; }
        .from-meta { font-size: 12px; line-height: 1.2; margin-top: 1px; }
        .weight { font-size: 12px; font-weight: 700; margin-top: 4px; }
        .to-name { font-size: 20px; font-weight: 700; line-height: 1.1; }
        .to-addr { font-size: 14px; font-weight: 700; line-height: 1.15; margin-top: 2px; white-space: pre-line; }
        .to-phone { font-size: 14px; font-weight: 700; margin-top: 3px; }
        .awb {
            font-size: 32px;
            font-weight: 700;
            text-align: center;
            padding: 6px 3px !important;
            letter-spacing: 0.3px;
            line-height: 1;
            word-break: break-word;
            vertical-align: middle !important;
        }
        .status-cell {
            width: 34%;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            vertical-align: middle !important;
            padding: 6px 4px !important;
        }
        .dest-cell {
            width: 66%;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            vertical-align: middle !important;
            padding: 6px 4px !important;
            line-height: 1.1;
        }
        .scan-wrap {
            text-align: center;
            padding: 5px 5px 3px !important;
            vertical-align: middle !important;
        }
        .scan-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .scan-barcode {
            width: 92%;
            height: 64px;
            display: block;
            margin: 0 auto;
        }
        .scan-awb {
            font-size: 12px;
            font-weight: 700;
            margin-top: 3px;
        }
        .pkg-title { font-size: 11px; font-weight: 700; margin-bottom: 1px; }
        .pkg-text { font-size: 12px; font-weight: 700; line-height: 1.15; }
        .footer-meta {
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            padding: 5px 6px !important;
            vertical-align: middle !important;
        }
    </style>
</head>
<body>
@foreach ($labels as $labelNumber)
    <div class="page">
        <table class="frame">
            <tr>
                <td class="row-border col-border" style="width: 38%; text-align: center; vertical-align: middle !important; padding: 6px;">
                    @if (is_file($logoPath))
                        <img class="logo" src="{{ $logoPath }}" alt="Kay Paolo Shipping">
                    @else
                        <strong style="font-size: 14px">KAY PAOLO</strong>
                    @endif
                </td>
                <td class="row-border" style="width: 62%">
                    <div class="from-label">From</div>
                    <div class="from-name">{{ $shipperName }}</div>
                    <div class="from-meta">{{ $shipperPhone }}</div>
                    <div class="from-meta" style="white-space: pre-line">{{ \Illuminate\Support\Str::limit(preg_replace("/\n{2,}/", "\n", trim($shipperAddress)), 90, '') }}</div>
                    <div class="weight">{{ $weightDim }}</div>
                </td>
            </tr>
            <tr>
                <td class="row-border" colspan="2" style="padding: 5px 7px">
                    <div class="to-label">To</div>
                    <div class="to-name">{{ $consigneeName }}</div>
                    <div class="to-addr">{{ \Illuminate\Support\Str::limit(preg_replace("/\n{2,}/", "\n", trim($consigneeAddress)), 110, '') }}</div>
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
                    <div class="scan-awb">{{ $barcodeValue }} | {{ $labelNumber }}</div>
                </td>
            </tr>
            <tr>
                <td class="row-border" colspan="2" style="padding: 5px 7px">
                    <div class="pkg-title">Package Contents</div>
                    <div class="pkg-text">{{ \Illuminate\Support\Str::limit($packageText, 80, '') }}</div>
                </td>
            </tr>
            <tr>
                <td class="footer-meta" colspan="2">
                    Invoice {{ $invoice }} | Delivery {{ $deliveryNumber }}
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
