<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 11px; color: #111111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 6px; }
        .customer { margin: 0 0 8px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 0 0 8px; }
        th, td { border: 1px solid #dddddd; padding: 4px 6px; vertical-align: top; }
        th { background: #f4f4f4; text-align: left; }
        .num { text-align: right; }
        .note { margin: 12px 0 4px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="customer">{{ $customer }}</p>

    <h2>{{ $summaryLabel }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ $skuLabel }}</th>
                <th>{{ $productLabel }}</th>
                <th class="num">{{ $soldLabel }}</th>
                <th class="num">{{ $creditedLabel }}</th>
                <th class="num">{{ $excessLabel }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $product['sku'] ?? '' }}</td>
                    <td>{{ $product['product_name'] ?? '' }}</td>
                    <td class="num">{{ $qty($product['qty_sold'] ?? 0) }}</td>
                    <td class="num">{{ $qty($product['qty_credited'] ?? 0) }}</td>
                    <td class="num">{{ $qty($product['qty_excess'] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ $detailLabel }}</h2>
    @foreach ($creditNotes as $note)
        <p class="note"><strong>{{ $noteLabel }}:</strong> {{ $note['order_number'] ?? '' }} · {{ $dateLabel }}: {{ $note['date'] ?? '' }}</p>
        <table>
            <thead>
                <tr>
                    <th>{{ $skuLabel }}</th>
                    <th>{{ $productLabel }}</th>
                    <th class="num">{{ $qtyLabel }}</th>
                    <th class="num">{{ $priceLabel }}</th>
                    <th class="num">{{ $soldLabel }}</th>
                    <th class="num">{{ $excessLabel }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($note['lines'] as $line)
                    <tr>
                        <td>{{ $line['sku'] ?? '' }}</td>
                        <td>{{ $line['product_name'] ?? '' }}</td>
                        <td class="num">{{ $qty($line['qty'] ?? 0) }}</td>
                        <td class="num">{{ $money($line['price'] ?? 0) }}</td>
                        <td class="num">{{ $qty($line['qty_sold'] ?? 0) }}</td>
                        <td class="num">{{ $qty($line['qty_excess'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
