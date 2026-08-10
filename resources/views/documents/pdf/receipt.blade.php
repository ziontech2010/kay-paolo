<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice }}</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1a202c; font-size: 12px; margin: 0; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .header td { border: 0; vertical-align: middle; }
        .logo { width: 110px; }
        .title { text-align: right; font-size: 28px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .info { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info td { width: 33.33%; vertical-align: top; border: 0; padding: 0 14px; font-size: 11px; line-height: 1.55; }
        .info td:first-child { padding-left: 0; }
        .info td:last-child { padding-right: 0; }
        .info-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #4b5563; margin-bottom: 4px; }
        .items { width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; margin-bottom: 16px; }
        .items th { font-size: 9px; text-transform: uppercase; color: #4b5563; border-bottom: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; padding: 8px 6px; text-align: left; }
        .items td { border-bottom: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; padding: 10px 6px; font-size: 11px; vertical-align: top; }
        .items th:last-child, .items td:last-child { border-right: 0; }
        .legal { font-size: 7.5px; line-height: 1.35; color: #4b5563; text-align: justify; }
        .legal div { margin-bottom: 4px; }
        .sign { font-size: 10px; font-weight: 700; margin-top: 10px; color: #111; }
        .notice { font-size: 9px; font-weight: 700; margin-top: 8px; color: #111; }
        .pricing { width: 100%; border-collapse: collapse; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; margin: 14px 0 18px; }
        .pricing td { padding: 12px 8px; text-align: center; }
        .price-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #6b7280; }
        .price-value { font-size: 16px; font-weight: 700; color: #111; }
        .total { text-align: right !important; }
        .total .price-value { font-size: 24px; }
        .notes { font-size: 10px; color: #4b5563; line-height: 1.5; margin-bottom: 18px; }
        .thanks { border-top: 1px solid #cbd5e1; padding-top: 12px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .footer { text-align: center; font-size: 10px; color: #4b5563; margin-top: 8px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 40%">
                @if (is_file($logoPath))
                    <img class="logo" src="{{ $logoPath }}" alt="Kay Paolo Shipping">
                @else
                    <strong>KAY PAOLO SHIPPING</strong>
                @endif
            </td>
            <td class="title">Invoice</td>
        </tr>
    </table>

    <table class="info">
        <tr>
            <td>
                <strong>{{ $serviceSummary }}</strong><br><br>
                <strong>Shipment no:</strong> {{ $trackingDisplay }}<br>
                <strong>Account No:</strong> {{ $accountNumber }}<br>
                <strong>Created:</strong> {{ $createdAt }}<br>
                <strong>Delivery Date:</strong> {{ $deliveryDate }}
            </td>
            <td>
                <div class="info-label">Shipper:</div>
                <strong>{{ $shipperName }}</strong><br>
                {!! nl2br(e($shipperAddress)) !!}<br>
                <strong>Phone:</strong> {{ $shipperPhone }}@if (!empty($shipperEmail))<br>
                <strong>Email:</strong> {{ $shipperEmail }}@endif
            </td>
            <td>
                <div class="info-label">Consignee:</div>
                <strong>{{ $consigneeName }}</strong><br>
                {!! nl2br(e($consigneeAddress)) !!}<br>
                <strong>Phone:</strong> {{ $consigneePhone }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 10%">No Pieces</th>
                <th style="width: 58%">Task Description</th>
                <th style="width: 10%; text-align: right">Weight</th>
                <th style="width: 10%; text-align: right">Volume</th>
                <th style="width: 12%; text-align: right">Dimension</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item['pieces'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td style="text-align: right">{{ $item['weight'] }} lbs</td>
                    <td style="text-align: right">{{ $item['volume'] }}</td>
                    <td style="text-align: right">{{ $item['dimensions'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td style="border-bottom: 0"></td>
                <td style="border-bottom: 0">
                    <div class="legal">
                        <div>I ____________________, hereby certify that this cargo does not contain any illegal, unauthorized, explosives, incendiaries, or hazardous materials. I consent to a search of this cargo.</div>
                        <div>(1) Cargo containing hazardous materials must be offered in accordance with Federal Hazardous Materials Regulations (49 CFR parts 171 through 180).</div>
                        <div>(2) A violation can result in five years' imprisonment and penalties of $250,000 or more (49 U.S.C. 5124). Kay Paolo Shipping will not be held responsible.</div>
                        <div>(4) Kay Paolo Shipping will only refund the amount declared for lost or damaged items. Undeclared items may incur additional fees charged to the shipper.</div>
                        <div>(5) Undeclared items are not eligible for refund or credit and may incur chargebacks before delivery. Claims must be reported within 24 hours of delivery or pickup.</div>
                        <div>(6) I certify that all information provided is accurate and complete.</div>
                        <div>(7) THERE MAY BE AN ADDITIONAL CUSTOMS AND DUTIES FEE UNDER HAITI CUSTOMS AUTHORITIES CONTROL.</div>
                        <div class="sign">Shipper’s signature : ___________________________</div>
                        <div class="notice">Special Notice: Due to current situational instabilities and territorial security issues in Haiti, we cannot guarantee any delivery date. The provided date is only an estimated timeframe.</div>
                    </div>
                </td>
                <td colspan="2" style="border-bottom: 0; font-weight: 700; color: #4b5563">Total Weight:</td>
                <td style="border-bottom: 0; text-align: right; font-weight: 700">{{ $totalWeight }} lbs</td>
            </tr>
        </tbody>
    </table>

    <table class="pricing">
        <tr>
            <td style="width: 25%">
                <div class="price-label">Subtotal</div>
                <div class="price-value">${{ number_format($freight, 2) }}</div>
            </td>
            <td style="width: 5%; font-size: 18px; color: #9ca3af">+</td>
            <td style="width: 25%">
                <div class="price-label">Tax</div>
                <div class="price-value">${{ number_format($tax, 2) }}</div>
            </td>
            <td style="width: 20%"></td>
            <td class="total" style="width: 25%">
                <div class="price-label">Total</div>
                <div class="price-value">${{ number_format($total, 2) }}</div>
            </td>
        </tr>
    </table>

    <div class="notes">
        * Payment Status: {{ $paymentStatus }}<br>
        * Total Value: ${{ number_format($declaredValue, 2) }}<br>
        * If you have any questions concerning this invoice, contact (732) 898-9303, info@kaypaoloshipping.com
    </div>

    <div class="thanks">Thank You For Your Business</div>
    <div class="footer">kaypaoloshipping.com &nbsp;|&nbsp; (732) 898-9303 &nbsp;|&nbsp; info@kaypaoloshipping.com</div>
</body>
</html>
