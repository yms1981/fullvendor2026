<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Orders\EnsurePositiveQtyForDocumentOrdersService;
use Illuminate\Console\Command;

/**
 * Sanitiza órdenes documento (tipo_d=D): qty positiva, ítems 0 fuera, órdenes vacías fuera, recálculo.
 */
class EnsurePositiveQtyForDocumentOrdersCommand extends Command
{
    protected $signature = 'order-details:ensure-positive-qty-for-d
                            {--company= : Limitar a un company_id}
                            {--dry-run : Solo cuenta qty negativas (no sanitiza ceros/vacías)}
                            {--full : Sanitiza completa (ABS + borrar qty 0 + borrar órdenes vacías + recalcular)}';

    protected $description = 'Sanitiza order_details/órdenes tipo_d=D (qty positiva, sin ítems 0, sin órdenes vacías).';

    public function handle(EnsurePositiveQtyForDocumentOrdersService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $companyOpt = $this->option('company');

        if ($dryRun) {
            $result = $service->repairAll(true);
            $this->info("Dry-run negative qty: found={$result['found']} (no updates).");

            return self::SUCCESS;
        }

        // Por defecto (y con --full): sanitización completa.
        if ($companyOpt !== null && $companyOpt !== '') {
            $companyIds = [(int) $companyOpt];
        } else {
            $companyIds = Company::query()
                ->orderBy('company_id')
                ->pluck('company_id')
                ->map(static fn ($id) => (int) $id)
                ->all();
        }

        $totals = [
            'qty_fixed' => 0,
            'zero_items_deleted' => 0,
            'orders_deleted' => 0,
            'recalculated' => 0,
            'orders_touched' => 0,
        ];

        foreach ($companyIds as $companyId) {
            if ($companyId < 1) {
                continue;
            }
            $stats = $service->sanitizeCompanyDocumentOrders($companyId);
            if ($stats['orders_touched'] > 0) {
                $this->info(
                    "company_id={$companyId}"
                    ." touched={$stats['orders_touched']}"
                    ." qty_fixed={$stats['qty_fixed']}"
                    ." zero_deleted={$stats['zero_items_deleted']}"
                    ." orders_deleted={$stats['orders_deleted']}"
                    ." recalculated={$stats['recalculated']}"
                );
            }
            foreach ($totals as $k => $_) {
                $totals[$k] += (int) ($stats[$k] ?? 0);
            }
        }

        $this->info(
            'Done.'
            ." touched={$totals['orders_touched']}"
            ." qty_fixed={$totals['qty_fixed']}"
            ." zero_deleted={$totals['zero_items_deleted']}"
            ." orders_deleted={$totals['orders_deleted']}"
            ." recalculated={$totals['recalculated']}"
        );

        return self::SUCCESS;
    }
}