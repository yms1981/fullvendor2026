<?php

namespace App\Services\AppSalesman;

use App\Models\Company;
use App\Models\FormatNumeric;

/**
 * Formatos de moneda y fecha de la compañía para la app salesman.
 */
class AppSalesmanCompanyFormatsService
{
    /**
     * @return array{status:string, company_id?:int, currency?:array, numeric?:array, date?:array, error?:string}
     */
    public function getFormats(int $companyId): array
    {
        if ($companyId <= 0) {
            return ['status' => '0', 'error' => 'company_id_required'];
        }

        /** @var Company|null $company */
        $company = Company::query()
            ->with(['currencyModel', 'formatDate', 'format_numeric'])
            ->where('company_id', $companyId)
            ->first();

        if (! $company) {
            return ['status' => '0', 'error' => 'company_not_found'];
        }

        $currency = $company->currencyModel;
        $numeric = $company->format_numeric;
        if (! $numeric) {
            $numeric = FormatNumeric::default();
        }
        $date = $company->formatDate;

        $symbol = is_object($currency) ? trim((string) ($currency->symbol ?? '')) : '';
        if ($symbol === '') {
            $symbol = '$';
        }
        $code = is_object($currency) ? trim((string) ($currency->code ?? '')) : '';
        if ($code === '') {
            $code = 'USD';
        }

        $digits = is_object($numeric) ? (int) ($numeric->digits ?? 2) : 2;
        $radix = is_object($numeric) ? (string) ($numeric->radixPoint ?? '.') : '.';
        $group = is_object($numeric) ? (string) ($numeric->groupSeparator ?? ',') : ',';
        if ($radix === '') {
            $radix = '.';
        }
        if ($group === '') {
            $group = ',';
        }

        $formatPhp = is_object($date) ? trim((string) ($date->format_php ?? '')) : '';
        if ($formatPhp === '') {
            $formatPhp = 'm/d/Y';
        }
        $formatJs = is_object($date) ? trim((string) ($date->format_js ?? '')) : '';
        $monthYear = is_object($date) ? trim((string) ($date->month_year ?? '')) : '';

        return [
            'status' => '1',
            'success' => true,
            'company_id' => $companyId,
            'currency' => [
                'code' => $code,
                'symbol' => $symbol,
                'name' => is_object($currency) ? (string) ($currency->name ?? '') : '',
            ],
            'numeric' => [
                'format_id' => is_object($numeric) ? (int) ($numeric->format_id ?? 0) : 0,
                'numeric_format' => is_object($numeric) ? (string) ($numeric->numeric_format ?? '') : '',
                'digits' => $digits,
                'radix_point' => $radix,
                'group_separator' => $group,
                'code_regional' => is_object($numeric) ? (string) ($numeric->code_regional ?? '') : '',
                // Ejemplo listo para la app (20008 → "$20,008.00" según empresa)
                'sample' => $symbol.number_format(20008, $digits, $radix, $group),
            ],
            'date' => [
                'format_id' => is_object($date) ? (int) ($date->id ?? 0) : 0,
                'format_php' => $formatPhp,
                'format_js' => $formatJs,
                'month_year' => $monthYear !== '' ? $monthYear : 'M-Y',
            ],
        ];
    }
}
