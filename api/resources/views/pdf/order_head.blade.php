<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: dejavusans, sans-serif;
            font-size: 9.5pt;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }
        .muted { color: #64748b; }
        .small { font-size: 8pt; }
        .upper {
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }
        .party-name {
            font-size: 10.5pt;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
        }
        .party-line {
            margin: 0 0 3px 0;
            color: #334155;
        }
        .party-label {
            color: #64748b;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .items-table th {
            background: #0f172a;
            color: #ffffff;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 9px 8px;
            border: none;
        }
        .items-table td {
            padding: 8px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .items-table tr:last-child td {
            border-bottom: 1px solid #cbd5e1;
        }
        .num { text-align: right; white-space: nowrap; }
        .product-name {
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 2px 0;
        }
        .product-sku {
            font-size: 8pt;
            color: #64748b;
            margin: 0;
        }
        .product-img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }
        .notes-box {
            margin-top: 18px;
            padding: 12px 14px;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
        }
        .notes-title {
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin: 0 0 6px 0;
        }
        .notes-content {
            margin: 0;
            white-space: pre-wrap;
            color: #334155;
            font-size: 9pt;
        }
    </style>
</head>
<body>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 18px;">
    <tr>
        <td style="background-color: #dc2626; height: 4px; font-size: 1px; line-height: 1px;">&nbsp;</td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
    <tr>
        <td width="58%" valign="middle">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    @if(!empty($logoDataUrl))
                    <td valign="middle" style="padding-right: 14px;">
                        <img src="{{ $logoDataUrl }}" alt="" style="max-height: 52px; max-width: 160px;" />
                    </td>
                    @endif
                    <td valign="middle">
                        <div class="upper" style="font-size: 8pt; color: #64748b; margin-bottom: 4px;">FullVendor</div>
                        <div style="font-size: 22pt; font-weight: 700; color: #0f172a; line-height: 1.1;">{{ $title }}</div>
                        <div style="width: 48px; height: 3px; background: #dc2626; margin-top: 8px;"></div>
                    </td>
                </tr>
            </table>
        </td>
        <td width="42%" valign="middle" align="right">
            <table cellpadding="0" cellspacing="0" align="right" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                <tr>
                    <td style="padding: 10px 14px;">
                        <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td class="party-label" style="padding-bottom: 5px;">{{ $orderNumberLabel }}</td>
                                <td align="right" style="padding-bottom: 5px; font-weight: 700; font-size: 11pt; color: #0f172a;">{{ $order->order_number ?? $order->order_id }}</td>
                            </tr>
                            <tr>
                                <td class="party-label">{{ $dateLabel }}</td>
                                <td align="right" style="font-weight: 600; color: #334155;">{{ $orderDate }}</td>
                            </tr>
                            @if($sellerName !== '')
                            <tr>
                                <td class="party-label" style="padding-top: 5px;">{{ $labelSeller }}</td>
                                <td align="right" style="padding-top: 5px; color: #334155;">{{ $sellerName }}</td>
                            </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 22px;">
    <tr>
        <td width="48%" valign="top" style="padding-right: 8px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e2e8f0; background: #ffffff;">
                <tr>
                    <td style="padding: 0;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding: 8px 12px 0;">
                                    <span style="display: inline-block; background: #16a34a; color: #fff; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 3px 10px;">{{ $vendorLabel }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 10px 12px 12px;">
                                    @if($companyName !== '')<p class="party-name">{{ $companyName }}</p>@endif
                                    @if($companyAddress !== '')
                                        <p class="party-line"><span class="party-label">{{ $labelAddress }}:</span> {{ $companyAddress }}</p>
                                    @endif
                                    @if($companyPhone !== '')
                                        <p class="party-line"><span class="party-label">{{ $labelPhone }}:</span> {{ $companyPhone }}</p>
                                    @endif
                                    @if($companyEmail !== '')
                                        <p class="party-line" style="margin-bottom: 0;"><span class="party-label">{{ $labelEmail }}:</span> {{ $companyEmail }}</p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
        <td width="4%"></td>
        <td width="48%" valign="top" style="padding-left: 8px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e2e8f0; background: #ffffff;">
                <tr>
                    <td style="padding: 0;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding: 8px 12px 0;">
                                    <span style="display: inline-block; background: #16a34a; color: #fff; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 3px 10px;">{{ $shipToLabel }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 10px 12px 12px;">
                                    @if($customerName !== '')<p class="party-name">{{ $customerName }}</p>@endif
                                    @if($customerContact !== '')<p class="party-line muted small" style="margin-top: -2px;">{{ $customerContact }}</p>@endif
                                    @if($customerAddress !== '')
                                        <p class="party-line"><span class="party-label">{{ $labelAddress }}:</span> {{ $customerAddress }}</p>
                                    @endif
                                    @if($customerPhone !== '')
                                        <p class="party-line"><span class="party-label">{{ $labelPhone }}:</span> {{ $customerPhone }}</p>
                                    @endif
                                    @if($customerCell !== '' && $customerCell !== $customerPhone)
                                        <p class="party-line"><span class="party-label">{{ $labelMobile }}:</span> {{ $customerCell }}</p>
                                    @endif
                                    @if($customerEmail !== '')
                                        <p class="party-line" style="margin-bottom: 0;"><span class="party-label">{{ $labelEmail }}:</span> {{ $customerEmail }}</p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
    <tr>
        <td>
            <span class="upper small muted">{{ $productsLabel }}</span>
            <span class="small muted" style="margin-left: 8px;">({{ $itemCount }})</span>
        </td>
    </tr>
</table>

<table class="items-table" cellpadding="0" cellspacing="0">
    <thead>
    <tr>
        <th style="width: 52px; text-align: center;">{{ $imageLabel }}</th>
        <th>{{ $descLabel }}</th>
        <th class="num" style="width: 88px;">{{ $unitPriceLabel }}</th>
        <th class="num" style="width: 68px;">{{ $qtyLabel }}</th>
        <th class="num" style="width: 92px;">{{ $totalLabel }}</th>
    </tr>
    </thead>
    <tbody>
