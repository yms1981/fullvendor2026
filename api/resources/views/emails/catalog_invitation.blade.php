<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Email</title>
</head>
@php
    /** Misma base que MVC template_email.php (base_url): front público, no APP_URL de la API. */
    $webBase = rtrim((string) config('fullvendor.web_url'), '/');
    $catalogLink = $webBase . '/catalogo/producto?token=' . rawurlencode((string) $token);
@endphp
<body style="background-color:#e2e1e0;font-family:Open Sans,sans-serif;font-size:100%;font-weight:400;line-height:1.4;color:#000;">
<table style="max-width:600px;margin:20px auto 10px;background-color:#fff;padding:20px;border-radius:3px;border-top:solid 10px #000000;">
    <thead>
    <tr>
        <th style="text-align:left;">
            @if (!empty($company->image))
                <img style="max-width: 150px;" src="{{ $webBase }}/uploads/company/{{ $company->image }}" alt="{{ $company->name }}">
            @endif
        </th>
        <th style="text-align:right;font-weight:400;"></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td colspan="2" style="height:2px;background:#232323"></td>
    </tr>
    <tr>
        <td colspan="2" style="padding:20px 0">
            <h3>{{ __('cmp-catalog.saludo') }} {{ $customer->business_name. ' ('. $customer->name . ')' }}</h3>

            @if(!empty($email_text))
                <p style="color: #000;">{{ $email_text }}</p>
            @endif

            <p style="color: #000;">{{ __('cmp-catalog.asi_son') }}</p>
            <p style="color: #000;">
                {{ __('cmp-catalog.podras') }}
                <a href="{{ $catalogLink }}">{{ __('cmp-catalog.enlace') }}</a>
            </p>
        </td>
    </tr>

    <tr>
        <td style="width:50%;padding:0;vertical-align:top">
            <br>{{ __('cmp-catalog.gracias') }}<br>

            @if(!empty($seller))
                <strong>{{ __('cmp-catalog.seller') }}: </strong>{{ $seller->user->name ?? 'sin nombre'}}<br>
                <strong>{{ __('cmp-catalog.phone') }}: </strong>{{ $seller->phone_number }}<br>
                <strong>Email: </strong>{{ $seller->user->email ?? 'sin email' }}<br><br>
            @endif

            <strong>{{ __('cmp-catalog.valido_hasta') }}: </strong>{{ $fecha_ex }}
        </td>
    </tr>

    <tr>
        <td colspan="2" style="padding: 10px 10px; text-align: center; font-size: 11px; border-top: 1px solid #595959; border-bottom: 1px solid #595959;">
            {{ __('cmp-catalog.mensaje_automatizado') }}
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>
