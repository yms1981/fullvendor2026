<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\ProductRatingService;
use Illuminate\Console\Command;

class SyncProductRatingsCommand extends Command
{
    protected $signature = 'product-ratings:sync
                            {--company= : Limitar a un company_id}
                            {--force : Recalcular todos los ratings (por defecto solo faltantes)}';

    protected $description = 'Rellena o recalcula product_ratings por empresa desde ventas (o rating estándar).';

    public function handle(ProductRatingService $service): int
    {
        $force = (bool) $this->option('force');
        $companyOpt = $this->option('company');

        if ($companyOpt !== null && $companyOpt !== '') {
            $companyIds = [(int) $companyOpt];
        } else {
            $companyIds = Company::query()
                ->orderBy('company_id')
                ->pluck('company_id')
                ->map(static fn ($id) => (int) $id)
                ->all();
        }

        $total = 0;
        foreach ($companyIds as $companyId) {
            if ($companyId < 1) {
                continue;
            }
            $written = $service->syncCompany($companyId, $force);
            $this->info("company_id={$companyId} rows={$written}");
            $total += $written;
        }

        $this->info("Done. total_rows={$total}");

        return self::SUCCESS;
    }
}
