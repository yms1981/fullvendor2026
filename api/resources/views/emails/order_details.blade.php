<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Invoice - {{ $data['order_number'] }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css"
          integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        body {
            padding: 0;
            font-family: sans-serif;
            font-size: 12px;
            line-height: 1.5em;
            color: #202124;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: sans-serif !important;
            color: #202124;
        }
        table {
            border-collapse: collapse;
        }
        .borde-address {
            border: 0.3px solid #6c757d;
            padding: 10px;
            border-radius: 10px;
            color: #202124;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            user-select: none;
            background-color: transparent;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: all 0.15s ease-in-out;
        }
        .btn-success {
            color: #fff;
            background-color: #1EA910;
            border-color: #1EA910;
        }
        .btn-danger {
            color: #fff;
            background-color: #CF1C00;
            border-color: #CF1C00;
        }
        .badge {
            border-radius: 4px;
            font-size: 8px;
            padding: 0.375rem 0.5625rem;
        }
        .companyImg {
            float: right;
            max-height: 75px;
            max-width: 200px;
        }
    </style>
</head>

<body>
<div style="width: 100%; margin-left: 0; font-size: 16px;">
    <!-- Encabezado -->
    <table id="table-encabezado" style="width: 100%;">
        <tr>
            <td style="width: 50%; color: #202124;">
                <p>{{ $data['descriptions'] }}</p>
                <p><strong>Número de Orden: </strong>{{ $data['order_number'] }}</p>
                <p><strong>Dirección de Envío: </strong>{{ $data['address_dispatch'] }}</p>
                <p><strong>Válido hasta: </strong>{{ $data['expiration_date'] }}</p>
            </td>
            <td style="width: 50%; text-align: right;">
                @if ($data['company']['image'])
                    <img src="{{ $data['company']['image'] }}" class="companyImg" alt="Logo de la Empresa">
                @else
                    <img src="{{ asset('uploads/default-logo.png') }}" class="companyImg" alt="Logo Predeterminado">
                @endif
            </td>
        </tr>
    </table>

    <!-- Detalles del Cliente y la Empresa -->
    @if (isset($data['show_information']))
        <table style="width:100%; margin-top: 20px;">
            <tr>
                <td style="width: 40%; color: #202124;">
                    <strong>Información del Cliente</strong>
                    <ul>
                        <li><strong>Cliente: </strong>{{ $data['customer']['name'] }}</li>
                        <li><strong>Email: </strong>{{ $data['customer']['email'] }}</li>
                        <li><strong>Teléfono: </strong>{{ $data['customer']['phone'] }}</li>
                        <li><strong>Dirección: </strong>{{ $data['customer']['address'] }}</li>
                    </ul>
                </td>
                <td style="width: 40%; color: #202124;">
                    <strong>Información de la Empresa</strong>
                    <ul>
                        <li><strong>Empresa: </strong>{{ $data['company']['name'] }}</li>
                        <li><strong>Email: </strong>{{ $data['company']['email'] }}</li>
                        <li><strong>Teléfono: </strong>{{ $data['company']['phone'] }}</li>
                        <li><strong>Dirección: </strong>{{ $data['company']['address'] }}</li>
                    </ul>
                </td>
            </tr>
        </table>
    @endif

    <!-- Información del Vendedor -->
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td style="width: 100%; color: #202124;">
                <ul>
                    <li>
                        <strong>Vendedor: </strong>{{ $data['seller']['name'] }} ({{ $data['seller']['phone'] }})
                    </li>
                </ul>
            </td>
        </tr>
    </table>

    <!-- Productos -->
    @php($currencySymbol = $data['company']['currencySymbol'])
    <table id="table-detalle" style="width: 100%; margin-top: 20px;">
        <thead>
        <tr>
            <th>#</th>
            <th>Imagen</th>
            <th>Producto</th>
            <th>SKU</th>
            <th class="text-center">Cantidad</th>
            @if (!$data['hide_price'])
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($data['order_details'] as $index => $detail)
            @php($product = $detail['product'])
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="text-align: center;">
                    <img src="{{ $product['image'] ?? asset('uploads/noimg.png') }}" alt="Producto" style="height: 50px;">
                </td>
                <td>{{ $product['name'] }}</td>
                <td class="text-center">{{ $product['sku'] }}</td>
                <td class="text-center">{{ $detail['quantity'] }}</td>
                @if (!$data['hide_price'])
                    <td class="text-right">{{ $currencySymbol }}{{ $detail['price'] }}</td>
                    <td class="text-right">{{ $currencySymbol }}{{ $detail['price'] * $detail['quantity'] }}</td>
                @endif
            </tr>
            @if (!empty($detail['comments']))
                <tr>
                    <td colspan="7"><strong>Comentarios: </strong>{{ $detail['comments'] }}</td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>

    <!-- Total -->
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td style="text-align: right;"><strong>Subtotal:</strong></td>
            <td class="text-right">{{ $currencySymbol }}{{ $data['total_info']['sub_total'] }}</td>
        </tr>
        <tr>
            <td style="text-align: right;"><strong>Descuento:</strong></td>
            <td class="text-right">{{ $currencySymbol }}{{ $data['total_info']['discount_total'] }}</td>
        </tr>
        <tr>
            <td style="text-align: right;"><strong>Total:</strong></td>
            <td class="text-right">{{ $currencySymbol }}{{ $data['total_info']['total_orden'] }}</td>
        </tr>
    </table>

    <!-- Botones de Confirmar/Rechazar -->
    @if ($data['show_buttons'])
        <table style="width: 100%; margin-top: 20px; text-align: center;">
            <tr>
                <td>
                    <a href="{{ $data['reject_url'] }}" class="btn btn-danger">Rechazar</a>
                </td>
                <td>
                    <a href="{{ $data['confirm_url'] }}" class="btn btn-success">Confirmar</a>
                </td>
            </tr>
        </table>
    @endif

    <!-- Notas -->
    <div style="margin-top: 20px;">
        <p>Gracias,<br>{{ $data['company']['name'] }}</p>
        <p><strong>Notas de Entrega:</strong> {{ $data['customer']['dispatch_notes'] }}</p>
    </div>
</div>
</body>

</html>
