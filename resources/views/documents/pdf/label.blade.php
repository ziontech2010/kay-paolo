<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label {{ $trackingDisplay }}</title>
    <style>
        @page { margin: 16px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #000; font-size: 12px; margin: 0; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .header td { vertical-align: middle; border: 0; padding: 0; }
        .logo { width: 110px; }
        .header-meta { text-align: right; }
        .awb-text { font-size: 12px; font-weight: 700; margin-top: 2px; }
        .dash { border-top: 2px dashed #94a3b8; margin: 10px 0; }
        .details { width: 100%; border-collapse: collapse; border: 2px solid #000; margin-bottom: 10px; }
        .details th, .details td { border: 1px solid #000; padding: 8px 10px; vertical-align: top; width: 50%; }
        .title { font-size: 22px; font-weight: 700; text-align: center; padding: 6px !important; }
        .label { font-size: 10px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
        .addr { font-size: 12px; line-height: 1.4; white-space: pre-line; }
        .weight { font-weight: 700; margin-top: 12px; }
        .huge { font-size: 32px; font-weight: 700; text-align: center; margin: 10px 0 8px; letter-spacing: 0.5px; }
        .pkg { border: 2px solid #000; padding: 10px 12px; margin-bottom: 12px; }
        .pkg-title { font-weight: 700; margin-bottom: 4px; font-size: 12px; }
        .scan-zone {
            border: 2px solid #000;
            padding: 22px 14px 18px;
            text-align: center;
            min-height: 300px;
        }
        .scan-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 14px;
            color: #111;
        }
        .scan-zone img.scan-barcode {
            height: 120px;
            max-width: 94%;
        }
        .scan-awb {
            font-size: 14px;
            font-weight: 700;
            margin-top: 8px;
        }
        .scan-delivery {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            border-top: 1px dashed #94a3b8;
            padding-top: 10px;
        }
        .scan-delivery td {
            border: 0;
            vertical-align: middle;
            padding-top: 12px;
        }
        .scan-delivery img {
            height: 42px;
        }
        .scan-delivery-text {
            text-align: right;
            font-size: 11px;
            font-weight: 700;
        }
    </style>
</head>
<body>
@foreach ($labels as $labelNumber)
    <div class="page">
        <table class="header">
            <tr>
                <td style="width: 42%">
                    @if (is_file($logoPath))
                        <img class="logo" src="{{ $logoPath }}" alt="Kay Paolo Shipping">
                    @else
                        <strong>KAY PAOLO SHIPPING</strong>
                    @endif
                </td>
                <td class="header-meta">
                    <div class="awb-text">AWB No. {{ $labelNumber }}</div>
                    <div class="awb-text" style="font-weight: 600; margin-top: 2px">Invoice {{ $invoice }}</div>
                </td>
            </tr>
        </table>

        <div class="dash"></div>

        <table class="details">
            <tr>
                <th class="title">{{ $chargeStatus }}</th>
                <th class="title" style="font-size: 18px">{{ $destination }}</th>
            </tr>
            <tr>
                <td>
                    <div class="label">Sender</div>
                    <div class="addr"><strong>{{ $shipperName }}</strong>
{{ $shipperPhone }}

{{ $shipperAddress }}</div>
                    <div class="addr weight">{{ $weightDim }}</div>
                </td>
                <td>
                    <div class="label">Receiver</div>
                    <div class="addr"><strong>{{ $consigneeName }}</strong>

{{ $consigneeAddress }}

{{ $consigneePhone }}</div>
                </td>
            </tr>
        </table>

        <div class="huge">{{ $labelNumber }}</div>
        <div class="dash"></div>

        <div class="pkg">
            <div class="pkg-title">Package Contents:</div>
            <div>{{ $packageText }}</div>
        </div>

        <div class="scan-zone">
            <div class="scan-title">Scan Code</div>
            @if (($scanBarcodeUri ?? $barcodeUri) !== '')
                <img class="scan-barcode" src="{{ $scanBarcodeUri ?? $barcodeUri }}" alt="scan barcode">
            @endif
            <div class="scan-awb">AWB No. {{ $labelNumber }}</div>

            <table class="scan-delivery">
                <tr>
                    <td style="width: 58%; text-align: left">
                        @if ($deliveryBarcodeUri !== '')
                            <img src="{{ $deliveryBarcodeUri }}" alt="delivery barcode">
                        @endif
                    </td>
                    <td class="scan-delivery-text">
                        Delivery No. {{ $deliveryNumber }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endforeach
</body>
</html>
