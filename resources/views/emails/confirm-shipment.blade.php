@php
    $logoUrl = $logoUrl ?? asset('kay-paolo/assets/logo/kay-paolo.png');
    $brandName = $brandName ?? 'Kay Paolo Shipping';
    $supportEmail = $supportEmail ?? 'info@kaypaoloshipping.com';
    $supportPhone = $supportPhone ?? '(732) 898-9303';
    $supportAddress = $supportAddress ?? '414 Main St, Asbury Park, NJ 07712';
    $websiteUrl = $websiteUrl ?? 'https://kaypaoloshipping.com';
    $shipmentNumber = $shipmentNumber ?? 'Pending';
    $trackingNumber = $trackingNumber ?? $shipmentNumber;
    $packageLabel = $packageLabel ?? '1 package';
    $serviceName = $serviceName ?? 'Shipping Service';
    $createdAt = $createdAt ?? now()->format('M d, Y');
    $shipperName = $shipperName ?? $brandName;
    $shipperAddress = $shipperAddress ?? $supportAddress;
    $shipperContact = $shipperContact ?? $supportEmail;
    $consigneeName = $consigneeName ?? 'Destination Customer';
    $consigneeAddress = $consigneeAddress ?? 'Destination address pending';
    $consigneeContact = $consigneeContact ?? 'Phone pending';
    $labelUrl = $labelUrl ?? url('/shipment-label');
    $receiptUrl = $receiptUrl ?? url('/shipment-receipt');
    $trackingUrl = $trackingUrl ?? url('/tracking');
    $confirmationUrl = $confirmationUrl ?? url('/shipment-confirmation');
    $homeUrl = $homeUrl ?? url('/');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Shipment Confirmation | {{ $brandName }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; }
            .email-pad { padding-left: 18px !important; padding-right: 18px !important; }
            .hero-pad { padding: 28px 20px 30px !important; }
            .btn-cell { display: block !important; width: 100% !important; padding: 0 0 12px 0 !important; }
            .btn-link { display: block !important; width: 100% !important; box-sizing: border-box !important; }
            .party-cell { display: block !important; width: 100% !important; padding: 0 0 16px 0 !important; }
            .detail-value { font-size: 20px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; color:#0f172a; font-family: Inter, Arial, Helvetica, sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all;">
        Shipment booked successfully. Shipment number {{ $shipmentNumber }}.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f8fafc;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" class="email-shell" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px; max-width:600px;">

                    {{-- Header / logo --}}
                    <tr>
                        <td class="email-pad" style="padding:0 8px 20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="left" valign="middle">
                                        <a href="{{ $homeUrl }}" style="text-decoration:none;">
                                            <img src="{{ $logoUrl }}" alt="{{ $brandName }}" width="140" style="display:block; width:140px; max-width:140px; height:auto;">
                                        </a>
                                    </td>
                                    <td align="right" valign="middle" style="font-family: 'IBM Plex Mono', Consolas, monospace; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#b58a3d;">
                                        Shipment Confirmation
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Success alert --}}
                    <tr>
                        <td class="email-pad" style="padding:0 8px 18px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fef9e7; border-radius:4px; box-shadow:0 2px 10px rgba(0,0,0,0.02);">
                                <tr>
                                    <td width="4" style="background-color:#b58a3d; border-radius:4px 0 0 4px; font-size:0; line-height:0;">&nbsp;</td>
                                    <td style="padding:16px 20px; font-family: Inter, Arial, Helvetica, sans-serif; font-size:15px; font-weight:500; line-height:1.4; color:#0f172a;">
                                        @if (!empty($recipientName))
                                            Hi {{ $recipientName }}, your shipment has been booked successfully.
                                        @else
                                            Shipment booked successfully.
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Dark hero card (mirrors shipment-confirm-hero-card) --}}
                    <tr>
                        <td class="email-pad" style="padding:0 8px 18px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#111827; border-radius:4px; overflow:hidden;">
                                <tr>
                                    <td height="4" style="background-color:#b58a3d; font-size:0; line-height:0;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class="hero-pad" style="padding:40px 36px 42px;">
                                        <h1 style="margin:0 0 12px; font-family: Inter, Arial, Helvetica, sans-serif; font-size:22px; font-weight:800; letter-spacing:0.03em; line-height:1.25; text-transform:uppercase; color:#ffffff;">
                                            View Labels, Documents &amp; Receipt
                                        </h1>
                                        <p style="margin:0 0 28px; font-family: Inter, Arial, Helvetica, sans-serif; font-size:14.5px; font-weight:400; line-height:1.65; color:#94a3b8;">
                                            Your shipment has been created successfully. Use the links below to view or print the shipment labels and receipt.
                                        </p>

                                        {{-- Shipment number detail box --}}
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:28px;">
                                            <tr>
                                                <td style="background-color:#1e293b; border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:20px 24px;">
                                                    <div style="font-family: 'IBM Plex Mono', Consolas, monospace; font-size:11.5px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:#b58a3d; margin-bottom:8px;">
                                                        Shipment Number
                                                    </div>
                                                    <div class="detail-value" style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:24px; font-weight:700; letter-spacing:0.02em; color:#ffffff; line-height:1.2;">
                                                        {{ $shipmentNumber }}
                                                    </div>
                                                    <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:15px; font-weight:700; color:#cbd5e1; margin-top:8px;">
                                                        {{ $packageLabel }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Gold CTA buttons --}}
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td class="btn-cell" style="padding:0 12px 0 0;">
                                                    <a class="btn-link" href="{{ $labelUrl }}" target="_blank" style="display:inline-block; background-color:#b58a3d; color:#f8fafc; font-family: Inter, Arial, Helvetica, sans-serif; font-size:14px; font-weight:700; letter-spacing:0.02em; text-decoration:none; padding:14px 26px; border-radius:8px;">
                                                        Open Label
                                                    </a>
                                                </td>
                                                <td class="btn-cell" style="padding:0;">
                                                    <a class="btn-link" href="{{ $receiptUrl }}" target="_blank" style="display:inline-block; background-color:#b58a3d; color:#f8fafc; font-family: Inter, Arial, Helvetica, sans-serif; font-size:14px; font-weight:700; letter-spacing:0.02em; text-decoration:none; padding:14px 26px; border-radius:8px;">
                                                        Open Receipt
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Shipment summary card --}}
                    <tr>
                        <td class="email-pad" style="padding:0 8px 18px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#ffffff; border:1px solid #e2e8f0; border-radius:8px;">
                                <tr>
                                    <td style="padding:28px 28px 8px;">
                                        <div style="font-family: 'IBM Plex Mono', Consolas, monospace; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#64748b; margin-bottom:6px;">
                                            Shipment Details
                                        </div>
                                        <div style="font-family: Fraunces, Georgia, 'Times New Roman', serif; font-size:22px; font-weight:700; color:#071526; margin-bottom:18px;">
                                            {{ $serviceName }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 28px 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td width="50%" valign="top" style="padding:0 8px 12px 0;">
                                                    <div style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px;">
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:11px; font-weight:800; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Tracking</div>
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:14px; font-weight:700; color:#0f172a; margin-top:5px;">{{ $trackingNumber }}</div>
                                                    </div>
                                                </td>
                                                <td width="50%" valign="top" style="padding:0 0 12px 8px;">
                                                    <div style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px;">
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:11px; font-weight:800; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Created</div>
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:14px; font-weight:700; color:#0f172a; margin-top:5px;">{{ $createdAt }}</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 28px 28px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td class="party-cell" width="50%" valign="top" style="padding:0 10px 0 0;">
                                                    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:11px; font-weight:800; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Shipper</div>
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:16px; font-weight:700; color:#0f172a; margin:8px 0;">{{ $shipperName }}</div>
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:14px; line-height:1.65; color:#475569;">{{ $shipperAddress }}</div>
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:14px; line-height:1.65; color:#475569; margin-top:4px;">{{ $shipperContact }}</div>
                                                    </div>
                                                </td>
                                                <td class="party-cell" width="50%" valign="top" style="padding:0 0 0 10px;">
                                                    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:11px; font-weight:800; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Consignee</div>
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:16px; font-weight:700; color:#0f172a; margin:8px 0;">{{ $consigneeName }}</div>
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:14px; line-height:1.65; color:#475569;">{{ $consigneeAddress }}</div>
                                                        <div style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:14px; line-height:1.65; color:#475569; margin-top:4px;">{{ $consigneeContact }}</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Secondary actions --}}
                    <tr>
                        <td class="email-pad" align="center" style="padding:4px 8px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding:0 8px;">
                                        <a href="{{ $trackingUrl }}" style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:13px; font-weight:600; color:#2f6f62; text-decoration:underline;">Track Shipment</a>
                                    </td>
                                    <td style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:13px; color:#e2e8f0;">|</td>
                                    <td style="padding:0 8px;">
                                        <a href="{{ $confirmationUrl }}" style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:13px; font-weight:600; color:#2f6f62; text-decoration:underline;">View Confirmation</a>
                                    </td>
                                    <td style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:13px; color:#e2e8f0;">|</td>
                                    <td style="padding:0 8px;">
                                        <a href="{{ $homeUrl }}" style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:13px; font-weight:600; color:#2f6f62; text-decoration:underline;">Return to Home</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="email-pad" style="padding:0 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid #e2e8f0;">
                                <tr>
                                    <td align="center" style="padding:24px 12px 8px; font-family: Inter, Arial, Helvetica, sans-serif; font-size:14px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#111827;">
                                        Thank You For Your Business
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:0 12px 8px; font-family: Inter, Arial, Helvetica, sans-serif; font-size:12px; line-height:1.7; color:#475569;">
                                        {{ $supportAddress }}<br>
                                        <a href="tel:+17328989303" style="color:#475569; text-decoration:none;">{{ $supportPhone }}</a>
                                        &nbsp;·&nbsp;
                                        <a href="mailto:{{ $supportEmail }}" style="color:#475569; text-decoration:none;">{{ $supportEmail }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:0 12px 8px;">
                                        <a href="{{ $websiteUrl }}" style="font-family: Inter, Arial, Helvetica, sans-serif; font-size:12px; color:#b58a3d; text-decoration:none; font-weight:600;">kaypaoloshipping.com</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:12px 12px 0; font-family: Inter, Arial, Helvetica, sans-serif; font-size:11px; line-height:1.5; color:#94a3b8;">
                                        This email confirms your Kay Paolo Shipping booking. Questions about this shipment? Contact us at the details above.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
