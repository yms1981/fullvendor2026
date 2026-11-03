@foreach($details as $detail)
    @php
        if (!empty($useAssignedQuantities)) {
            $lineTotal = (float) ($detail->pdf_line_total ?? 0);
            $qtyText = !empty($detail->pdf_qty_unavailable)
                ? ($qtyUnavailableLabel ?? '—')
                : $fmtQty($detail->pdf_qty ?? 0);
        } else {
            $lineTotal = ($detail->qty ?? 0) * ($detail->sale_price ?? 0);
            $qtyText = $fmtQty($detail->qty ?? 0);
        }
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
        <td class="num">{{ $qtyText }}</td>
        <td class="num" style="font-weight: 700;">{{ $moneda }} {{ $fmtMoney($lineTotal) }}</td>
    </tr>
@endforeach
