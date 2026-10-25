<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FormatNumeric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

final class CatalogCartTotalsService
{
    /**
     * @return array{success: bool, total_orden: string, cantidad_productos: int, sub_total: string, discount_total: string}
     */
    public function totalsForCart(stdClass $cart): array
    {
        $default = [
            'success' => true,
            'total_orden' => '0.00',
            'cantidad_productos' => 0,
            'sub_total' => '0.00',
            'discount_total' => '0.00',
        ];

        $token = (string) ($cart->token_kor ?? '');
        $cartId = (int) ($cart->cart_id ?? 0);
        if ($token === '' && $cartId < 1) {
            return $default;
        }

        $subTotal = 0.0;
        $qtyTotal = 0;

        if (Schema::hasTable('monto_cart')) {
            $query = DB::table('monto_cart');
            if ($cartId > 0) {
                $query->where('cart_id', $cartId);
            }
            $row = $query->first();
            if ($row !== null) {
                $subTotal = (float) ($row->sub_total ?? 0);
                $discountTotal = (float) ($row->discount_total ?? 0);
                $total = (float) ($row->total ?? ($subTotal - $discountTotal));
                $qtyTotal = $this->sumQtyForToken($token, $cartId);

                return $this->formatTotals($cart, $total, $subTotal, $discountTotal, $qtyTotal);
            }
        }

        if (Schema::hasTable('detail_car')) {
            $detailQuery = DB::table('detail_car');
            if ($token !== '') {
                $detailQuery->where('token_car', $token);
            } elseif ($cartId > 0) {
                $detailQuery->where('cart_id', $cartId);
            }
            $agg = $detailQuery
                ->selectRaw('COALESCE(SUM(qty * sale_price), 0) AS sub_total, COALESCE(SUM(qty), 0) AS qty_total')
                ->first();
            if ($agg !== null) {
                $subTotal = (float) ($agg->sub_total ?? 0);
                $qtyTotal = (int) ($agg->qty_total ?? 0);
            }
        }

        $discountTotal = 0.0;
        $customerId = (int) ($cart->customer_id ?? 0);
        if ($customerId > 0 && Schema::hasTable('customers')) {
            $discountPct = (float) (DB::table('customers')->where('customer_id', $customerId)->value('discount') ?? 0);
            if ($discountPct > 0) {
                $discountTotal = $subTotal * ($discountPct / 100);
            }
        }

        $total = $subTotal - $discountTotal;

        return $this->formatTotals($cart, $total, $subTotal, $discountTotal, $qtyTotal);
    }

    private function sumQtyForToken(string $token, int $cartId): int
    {
        if (! Schema::hasTable('detail_car')) {
            return 0;
        }
        $q = DB::table('detail_car');
        if ($token !== '') {
            $q->where('token_car', $token);
        } elseif ($cartId > 0) {
            $q->where('cart_id', $cartId);
        }

        return (int) ($q->sum('qty') ?? 0);
    }

    /**
     * @return array{success: bool, total_orden: string, cantidad_productos: int, sub_total: string, discount_total: string}
     */
    private function formatTotals(stdClass $cart, float $total, float $subTotal, float $discountTotal, int $qtyTotal): array
    {
        $fmt = $this->resolveFormatter($cart);

        return [
            'success' => true,
            'total_orden' => $fmt($total),
            'cantidad_productos' => $qtyTotal,
            'sub_total' => $fmt($subTotal),
            'discount_total' => $fmt($discountTotal),
        ];
    }

    private function resolveFormatter(stdClass $cart): callable
    {
        $companyId = (int) ($cart->company_id ?? 0);
        $formatNumeric = null;
        if ($companyId > 0) {
            $company = Company::query()->find($companyId);
            if ($company !== null) {
                $formatNumeric = $company->format_id
                    ? FormatNumeric::query()->find((int) $company->format_id)
                    : FormatNumeric::query()->where('default', 1)->first();
            }
        }

        if ($formatNumeric !== null
            && isset($formatNumeric->digits, $formatNumeric->radixPoint, $formatNumeric->groupSeparator)) {
            return static function (float $n) use ($formatNumeric): string {
                return number_format(
                    $n,
                    (int) $formatNumeric->digits,
                    (string) $formatNumeric->radixPoint,
                    (string) $formatNumeric->groupSeparator
                );
            };
        }

        return static fn (float $n): string => number_format($n, 2, '.', ',');
    }
}
