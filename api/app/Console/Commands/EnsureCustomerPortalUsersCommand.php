<?php

namespace App\Console\Commands;

use App\Services\EnsureCustomerPortalUserService;
use Illuminate\Console\Command;

class EnsureCustomerPortalUsersCommand extends Command
{
    protected $signature = 'customers:ensure-portal-users
                            {--company= : Solo esta compañía (company_id)}
                            {--limit=500 : Máximo de clientes a procesar}';

    protected $description = 'Crea/vincula usuarios portal para clientes con email sin user (password vacío → 12345678).';

    public function handle(EnsureCustomerPortalUserService $service): int
    {
        $companyId = (int) $this->option('company');
        $limit = max(1, (int) $this->option('limit'));

        $this->info('Asegurando usuarios portal de clientes…');
        $stats = $service->ensureMissing($companyId > 0 ? $companyId : null, $limit);

        $this->table(
            ['Metric', 'Count'],
            [
                ['scanned', $stats['scanned']],
                ['fixed', $stats['fixed']],
                ['failed', $stats['failed']],
            ]
        );

        return self::SUCCESS;
    }
}
