<?php

namespace App\Services;

use App\Models\Company;
use App\Support\UploadsPath;
use Mpdf\Mpdf;

/**
 * PDF de excesos de devolución de un cliente: resumen de productos y detalle por nota de crédito.
 */
class CreditReturnOverSalesPdf
{
    public function render(int $companyId, int $customerId): ?string
    {
        $report = app(CreditReturnOverSalesService::class)->customerReport($companyId, $customerId);
        if ($report === null) {
            return null;
        }

        $company = Company::query()->with('format_numeric')->find($companyId);
        $languageId = (int) ($company->language_id ?? 1);
        $digits = (int) ($company?->format_numeric?->digits ?? 2);
        if ($digits < 0 || $digits > 6) {
            $digits = 2;
        }

        $label = static function (string $key) use ($languageId): string {
            return PlanLimitLang::get($languageId, 'cmp-orders', $key);
        };
        $qty = static function ($value): string {
            $number = (float) $value;

            return number_format($number, abs($number - round($number)) < 0.0001 ? 0 : 2, '.', ',');
        };
        $money = static function ($value) use ($digits): string {
            return number_format((float) $value, $digits, '.', ',');
        };

        $html = view('pdf.return_over_sales', [
            'title' => $label('return_over_sales_title'),
            'customer' => (string) ($report['customer_name'] ?? ''),
            'summaryLabel' => $label('return_over_sales_summary'),
            'detailLabel' => $label('return_over_sales_credit_detail'),
            'productLabel' => $label('return_over_sales_product'),
            'skuLabel' => $label('return_over_sales_sku'),
            'soldLabel' => $label('return_over_sales_sold'),
            'creditedLabel' => $label('return_over_sales_credited'),
            'excessLabel' => $label('return_over_sales_excess'),
            'noteLabel' => $label('return_over_sales_note'),
            'dateLabel' => $label('return_over_sales_date'),
            'qtyLabel' => $label('return_over_sales_qty'),
            'priceLabel' => $label('return_over_sales_price'),
            'products' => $report['products'],
            'creditNotes' => $report['credit_notes'],
            'qty' => $qty,
            'money' => $money,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => UploadsPath::mpdfTempDir($companyId),
            'default_font' => 'dejavusans',
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
