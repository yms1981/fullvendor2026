<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 9px; color: #111827; }
        .fv-pdf-title { text-align: center; font-size: 14px; font-weight: bold; margin: 0 0 12px; text-transform: uppercase; }
        .fv-pdf-header { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .fv-pdf-header td { vertical-align: top; padding: 4px 6px; }
        .fv-pdf-header__logo { text-align: center; width: 28%; }
        .fv-pdf-header__logo img { width: 72px; height: 72px; object-fit: contain; }
        .fv-pdf-header__meta { font-size: 9px; line-height: 1.5; }
        .fv-pdf-header__meta strong { color: #374151; }
        table.fv-pdf-table { width: 100%; border-collapse: collapse; }
        table.fv-pdf-table th,
        table.fv-pdf-table td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; vertical-align: top; }
        table.fv-pdf-table th { background-color: #f3f4f6; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        table.fv-pdf-table td.fv-pdf-col-real { min-width: 70px; height: 18px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
@php
    $labels = is_array($labels ?? null) ? $labels : [];
    $company = is_array($company ?? null) ? $company : [];
    $title = (string) ($labels['title'] ?? 'Product inventory');
    $printDateLabel = (string) ($labels['print_date'] ?? 'Print date');
    $companyLabel = (string) ($labels['company'] ?? 'Company');
    $phoneLabel = (string) ($labels['phone'] ?? 'Phone');
    $emailLabel = (string) ($labels['email'] ?? 'Email');
    $addressLabel = (string) ($labels['address'] ?? 'Address');
@endphp

<h1 class="fv-pdf-title">{{ $title }}</h1>

<table class="fv-pdf-header">
    <tr>
        <td class="fv-pdf-header__meta" style="width:36%;">
            <strong>{{ $companyLabel }}:</strong> {{ $company['name'] ?? '' }}<br>
            @if(!empty($company['phone']))
                <strong>{{ $phoneLabel }}:</strong> {{ $company['phone'] }}<br>
            @endif
            @if(!empty($company['email']))
                <strong>{{ $emailLabel }}:</strong> {{ $company['email'] }}<br>
            @endif
            @if(!empty($company['address']))
                <strong>{{ $addressLabel }}:</strong> {{ $company['address'] }}
            @endif
        </td>
        <td class="fv-pdf-header__logo">
            @if(!empty($logoDataUrl))
                <img src="{{ $logoDataUrl }}" alt="">
            @endif
        </td>
        <td class="fv-pdf-header__meta" style="width:36%; text-align:right;">
            <strong>{{ $printDateLabel }}:</strong> {{ $printDate ?? '' }}
        </td>
    </tr>
</table>

<table class="fv-pdf-table">
    <thead>
    <tr>
        @foreach($headings as $heading)
            <th>{{ $heading }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
