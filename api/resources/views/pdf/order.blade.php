@php
    use App\Models\FormatNumeric;

    $lang = $lang ?? [];
    $t = $lang;
    $title = (isset($order->tipo_d) && strtoupper($order->tipo_d) === 'C')
        ? ($t['pdf_title_credit_note'] ?? $t['credit_note'] ?? 'CREDIT NOTE')
        : ($t['pdf_title_order'] ?? 'ORDER');
    $orderNumberLabel = $t['pdf_order_number'] ?? 'Order #';
    $dateLabel = $t['pdf_date'] ?? 'Date';
    $vendorLabel = $t['pdf_vendor'] ?? 'VENDOR';
    $shipToLabel = $t['pdf_ship_to'] ?? 'SHIP TO';
    $productsLabel = $t['pdf_products_services'] ?? 'PRODUCTS OR SERVICES';
    $descLabel = $t['pdf_description'] ?? 'Description';
    $unitPriceLabel = $t['pdf_unit_price'] ?? 'Unit Price';
    $qtyLabel = $t['pdf_quantity'] ?? 'Quantity';
    $totalLabel = $t['pdf_total'] ?? 'Total';
    $subtotalLabel = $t['pdf_subtotal'] ?? 'Subtotal';
    $discountLabel = $t['pdf_discount'] ?? 'Discount';
    $notesLabel = $t['pdf_notes'] ?? 'Notes';
    $imageLabel = $t['pdf_image'] ?? 'Image';
    $labelPhone = $t['phone'] ?? 'Phone';
    $labelEmail = $t['email'] ?? 'Email';
    $labelAddress = $t['address'] ?? 'Address';
    $labelSeller = $t['seller'] ?? 'Seller';
    $productImageDataUrls = $productImageDataUrls ?? [];

    $formatNumeric = null;
    if ($order->relationLoaded('company') && $order->company && $order->company->relationLoaded('format_numeric')) {
        $formatNumeric = $order->company->format_numeric;
    }
    if (!$formatNumeric) {
        $formatNumeric = FormatNumeric::default();
    }
    $digits = (int) ($formatNumeric->digits ?? 2);
    $fmtMoney = static function ($value) use ($formatNumeric, $digits) {
        if ($formatNumeric) {
            return $formatNumeric->formatNumber((float) $value);
        }
        return number_format((float) $value, $digits, '.', ',');
    };
    $fmtQty = static function ($value) use ($digits) {
        $n = (float) $value;
        if (floor($n) == $n) {
            return number_format($n, 0, '.', ',');
        }
        return number_format($n, $digits, '.', ',');
    };

    $moneda = '$';
    if ($order->relationLoaded('company') && $order->company) {
        $c = $order->company;
        $moneda = (isset($c->currencyModel) && $c->currencyModel ? $c->currencyModel->symbol : null)
            ?? (isset($c->currencies[0]) ? $c->currencies[0]->symbol : null) ?? '$';
    }

    $companyName = '';
    $companyAddress = '';
    $companyPhone = '';
    $companyEmail = '';
    if ($order->relationLoaded('company') && $order->company) {
        $co = $order->company;
        $companyName = trim($co->company_name ?? $co->name ?? '');
        $companyEmail = trim($co->email ?? $co->company_email ?? '');
        $companyAddress = trim($co->address ?? '');
        if ($companyAddress === '' && $co->relationLoaded('addresses') && $co->addresses->isNotEmpty()) {
            $addr = $co->addresses->first();
            $parts = array_filter([
                trim(($addr->street ?? '') . ' ' . ($addr->street_number ?? '')),
                $addr->zone ?? null,
                ($addr->relationLoaded('city') && $addr->city) ? $addr->city->name : null,
                ($addr->relationLoaded('state') && $addr->state) ? $addr->state->name : null,
                $addr->zipcode ?? null,
                ($addr->relationLoaded('country') && $addr->country) ? $addr->country->name : null,
            ], fn ($v) => trim((string) $v) !== '');
            $companyAddress = implode(', ', $parts);
        }
        $companyPhone = trim($co->phone_number ?? $co->phone ?? '');
    }

    $customerName = '';
    $customerContact = '';
    $customerAddress = '';
    $customerPhone = '';
    $customerCell = '';
    $customerEmail = '';
    if ($order->relationLoaded('customer') && $order->customer) {
        $cu = $order->customer;
        $business = trim($cu->business_name ?? '');
        $contact = trim($cu->name ?? '');
        $customerName = $business !== '' ? $business : $contact;
        $customerContact = ($business !== '' && $contact !== '' && $contact !== $business) ? $contact : '';
        $customerEmail = trim($cu->email ?? '');
        $customerPhone = trim($cu->phone ?? $cu->phone_number ?? '');
        $customerCell = trim($cu->cell_phone ?? '');
        if ($order->relationLoaded('address') && $order->address) {
            $addr = $order->address;
            $parts = array_filter([
                trim(($addr->street ?? '') . ' ' . ($addr->street_number ?? '')),
                $addr->zone ?? null,
                ($addr->relationLoaded('city') && $addr->city) ? $addr->city->name : null,
                ($addr->relationLoaded('state') && $addr->state) ? $addr->state->name : null,
                $addr->zipcode ?? null,
                ($addr->relationLoaded('country') && $addr->country) ? $addr->country->name : null,
            ], fn ($v) => trim((string) $v) !== '');
            $customerAddress = implode(', ', $parts);
        }
        if ($customerAddress === '' && !empty($cu->commercial_address)) {
            $customerAddress = trim($cu->commercial_address);
        }
        if ($customerAddress === '' && !empty($cu->dispatch_address)) {
            $customerAddress = trim($cu->dispatch_address);
        }
    }

    $sellerName = '';
    if ($order->relationLoaded('seller') && $order->seller) {
        $sellerName = trim($order->seller->user->name ?? $order->seller->name ?? '');
    }

    $orderDate = $order->created ? \App\Support\DisplayDateFormat::format($order->created) : '';
    $subtotal = (float) ($order->subtotal ?? 0);
    $discount = (float) ($order->descuento ?? $order->discount ?? 0);
    $grandTotal = (float) ($order->total ?? max(0, $subtotal - $discount));
    $itemCount = $order->order_details ? $order->order_details->count() : 0;
@endphp
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

{{-- Barra de acento FullVendor --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 18px;">
    <tr>
        <td style="background-color: #dc2626; height: 4px; font-size: 1px; line-height: 1px;">&nbsp;</td>
    </tr>
</table>

{{-- Encabezado --}}
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

{{-- Tarjetas proveedor / cliente --}}
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
                                        <p class="party-line"><span class="party-label">{{ $t['cell_phone'] ?? 'Mobile' }}:</span> {{ $customerCell }}</p>
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

{{-- Productos --}}
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
    @foreach($order->order_details ?? [] as $detail)
        @php
            $lineTotal = ($detail->qty ?? 0) * ($detail->sale_price ?? 0);
            $proId = $detail->product ? ($detail->product->pro_id ?? $detail->product->product_id ?? null) : null;
            $imgDataUrl = $proId ? ($productImageDataUrls[$proId] ?? '') : '';
            $productName = $detail->product->name ?? '-';
            $productSku = $detail->product->sku ?? null;
        @endphp
        <tr>
            <td style="text-align: center;">
                @if($imgDataUrl)
                    <img src="{{ $imgDataUrl }}" alt="" class="product-img" />
                @else
                    <span class="muted">—</span>
                @endif
            </td>
            <td>
                <p class="product-name">{{ $productName }}</p>
                @if($productSku)<p class="product-sku">{{ $productSku }}</p>@endif
            </td>
            <td class="num">{{ $moneda }} {{ $fmtMoney($detail->sale_price ?? 0) }}</td>
            <td class="num">{{ $fmtQty($detail->qty ?? 0) }}</td>
            <td class="num" style="font-weight: 700;">{{ $moneda }} {{ $fmtMoney($lineTotal) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- Totales --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16px;">
    <tr>
        <td width="55%"></td>
        <td width="45%" valign="top">
            <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e2e8f0;">
                @if($subtotal > 0)
                <tr>
                    <td style="padding: 8px 12px; background: #f8fafc; color: #64748b; font-size: 8.5pt; font-weight: 700; text-transform: uppercase;">{{ $subtotalLabel }}</td>
                    <td align="right" style="padding: 8px 12px; background: #f8fafc; font-weight: 600;">{{ $moneda }} {{ $fmtMoney($subtotal) }}</td>
                </tr>
                @endif
                @if($discount > 0)
                <tr>
                    <td style="padding: 8px 12px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 8.5pt; font-weight: 700; text-transform: uppercase;">{{ $discountLabel }}</td>
                    <td align="right" style="padding: 8px 12px; border-top: 1px solid #e2e8f0; color: #dc2626; font-weight: 600;">- {{ $moneda }} {{ $fmtMoney($discount) }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 11px 12px; background: #0f172a; color: #ffffff; font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">{{ $totalLabel }}</td>
                    <td align="right" style="padding: 11px 12px; background: #0f172a; color: #ffffff; font-size: 12pt; font-weight: 700;">{{ $moneda }} {{ $fmtMoney($grandTotal) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if(!empty($notesText))
<div class="notes-box">
    <p class="notes-title">{{ $notesLabel }}</p>
    <p class="notes-content">{{ $notesText }}</p>
</div>
@endif

{{-- Pie --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 22px;">
    <tr>
        <td style="border-top: 1px solid #e2e8f0; padding-top: 10px;" class="small muted" align="center">
            FullVendor · {{ $companyName !== '' ? $companyName : 'Order document' }}
        </td>
    </tr>
</table>

</body>
</html>
