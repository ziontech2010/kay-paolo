<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label {{ $trackingDisplay }}</title>
    <style>
        @page { margin: 4pt; }
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
            height: 568pt;
            page-break-inside: avoid;
            page-break-after: always;
        }
        .page:last-child { page-break-after: auto; }
        .frame {
            width: 100%;
            height: 568pt;
            border-collapse: collapse;
            border: 3px solid #000;
            table-layout: fixed;
        }
        .frame td {
            vertical-align: top;
            padding: 6pt 8pt;
            border: 0;
        }
        .row-border { border-bottom: 2px solid #000 !important; }
        .col-border { border-right: 2px solid #000 !important; }
        .top-row { height: 100pt; }
        .to-row { height: 124pt; }
        .awb-row { height: 75pt; }
        .route-row { height: 58pt; }
        .scan-row { height: 129pt; }
        .footer-row { height: 38pt; }
        .logo { width: 80pt; height: auto; }
        .from-label, .to-label {
            font-size: 10.5pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 2pt;
        }
        .from-name { font-size: 13pt; font-weight: 700; line-height: 1.12; }
        .from-meta { font-size: 10.5pt; line-height: 1.15; margin-top: 1pt; }
        .weight { font-size: 11pt; font-weight: 700; margin-top: 4pt; }
        .to-name { font-size: 22pt; font-weight: 700; line-height: 1.03; }
        .to-addr { font-size: 14.5pt; font-weight: 700; line-height: 1.08; margin-top: 4pt; white-space: pre-line; }
        .to-phone { font-size: 15.5pt; font-weight: 700; margin-top: 5pt; }
        .awb {
            font-size: 32pt;
            font-weight: 700;
            text-align: center;
            padding: 6pt 4pt !important;
            letter-spacing: 0.3px;
            line-height: 1;
            word-break: break-word;
            vertical-align: middle !important;
        }
        .status-cell {
            width: 34%;
            font-size: 22pt;
            font-weight: 700;
            text-align: center;
            vertical-align: middle !important;
            padding: 6pt 4pt !important;
        }
        .dest-cell {
            width: 66%;
            font-size: 21pt;
            font-weight: 700;
            text-align: center;
            vertical-align: middle !important;
            padding: 6pt 4pt !important;
            line-height: 1.1;
        }
        .scan-wrap {
            text-align: center;
            padding: 8pt 6pt 5pt !important;
            vertical-align: middle !important;
        }
        .scan-title {
            font-size: 10.5pt;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 4pt;
        }
        .scan-barcode {
            width: 92%;
            height: 62pt;
            display: block;
            margin: 0 auto;
        }
        .scan-awb {
            font-size: 12pt;
            font-weight: 700;
            margin-top: 4pt;
        }
        .footer-meta {
            font-size: 9.5pt;
            font-weight: 700;
            line-height: 1.15;
            text-align: center;
            padding: 6pt !important;
            vertical-align: middle !important;
        }
    </style>
</head>
<body>
@foreach ($labels as $labelNumber)
    <div class="page">
        <table class="frame">
            <tr class="top-row">
                <td class="row-border col-border" style="width: 38%; text-align: center; vertical-align: middle !important; padding: 6pt;">
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
                    @if (!empty($shipperEmail))
                        <div class="from-meta">{{ $shipperEmail }}</div>
                    @endif
                    <div class="from-meta" style="white-space: pre-line">{{ \Illuminate\Support\Str::limit(preg_replace("/\n{2,}/", "\n", trim($shipperAddress)), 90, '') }}</div>
                    <div class="weight">{{ $weightDim }}</div>
                </td>
            </tr>
            <tr class="to-row">
                <td class="row-border" colspan="2" style="padding: 8pt 9pt">
                    <div class="to-label">To</div>
                    <div class="to-name">{{ $consigneeName }}</div>
                    <div class="to-addr">{{ \Illuminate\Support\Str::limit(preg_replace("/\n{2,}/", "\n", trim($consigneeAddress)), 110, '') }}</div>
                    <div class="to-phone">{{ $consigneePhone }}</div>
                </td>
            </tr>
            <tr class="awb-row">
                <td class="row-border awb" colspan="2">{{ $labelNumber }}</td>
            </tr>
            <tr class="route-row">
                <td class="row-border col-border status-cell">{{ $chargeStatus }}</td>
                <td class="row-border dest-cell">{{ $destination }}</td>
            </tr>
            <tr class="scan-row">
                <td class="row-border scan-wrap" colspan="2">
                    <div class="scan-title">Scan Code</div>
                    @if (($scanBarcodeUri ?? $barcodeUri) !== '')
                        <img class="scan-barcode" src="{{ $scanBarcodeUri ?? $barcodeUri }}" alt="scan barcode">
                    @endif
                    <div class="scan-awb">{{ $barcodeValue }} | {{ $labelNumber }}</div>
                </td>
            </tr>
            <tr class="footer-row">
                <td class="footer-meta" colspan="2">
                    Invoice {{ $invoice }} | Delivery {{ $deliveryNumber }} | ETA {{ preg_replace('/^by\s+/i', '', $deliveryDate) }}
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
