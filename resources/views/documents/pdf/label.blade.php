<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label {{ $trackingDisplay }}</title>
    <style>
        @page { margin: 18px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #000; font-size: 12px; margin: 0; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header td { vertical-align: middle; border: 0; padding: 0; }
        .logo { width: 120px; }
        .barcode-wrap { text-align: right; }
        .barcode-wrap img { height: 48px; }
        .awb-text { font-size: 12px; font-weight: 700; margin-top: 4px; }
        .dash { border-top: 2px dashed #94a3b8; margin: 12px 0; }
        .details { width: 100%; border-collapse: collapse; border: 2px solid #000; margin-bottom: 16px; }
        .details th, .details td { border: 1px solid #000; padding: 10px 12px; vertical-align: top; width: 50%; }
        .title { font-size: 22px; font-weight: 700; text-align: center; padding: 6px !important; }
        .label { font-size: 10px; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; }
        .addr { font-size: 12px; line-height: 1.45; white-space: pre-line; }
        .weight { font-weight: 700; margin-top: 18px; }
        .huge { font-size: 34px; font-weight: 700; text-align: center; margin: 18px 0; }
        .lower { text-align: right; margin-bottom: 16px; }
        .lower img { height: 42px; }
        .pkg { border: 2px solid #000; padding: 12px; }
        .pkg-title { font-weight: 700; margin-bottom: 8px; }
    </style>
</head>
<body>
@foreach ($labels as $labelNumber)
    <div class="page">
        <table class="header">
            <tr>
                <td style="width: 40%">
                    @if (is_file($logoPath))
                        <img class="logo" src="{{ $logoPath }}" alt="Kay Paolo Shipping">
                    @else
                        <strong>KAY PAOLO SHIPPING</strong>
                    @endif
                </td>
                <td class="barcode-wrap">
                    @if ($barcodeUri !== '')
                        <img src="{{ $barcodeUri }}" alt="barcode"><br>
                    @endif
                    <div class="awb-text">AWB No. {{ $labelNumber }}</div>
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

        <div class="lower">
            @if ($deliveryBarcodeUri !== '')
                <img src="{{ $deliveryBarcodeUri }}" alt="delivery barcode"><br>
            @endif
            <div class="awb-text">Delivery No. {{ $deliveryNumber }}</div>
        </div>

        <div class="pkg">
            <div class="pkg-title">Package Contents:</div>
            <div>{{ $packageText }}</div>
        </div>
    </div>
@endforeach
</body>
</html>
