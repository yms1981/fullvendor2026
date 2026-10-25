<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * @deprecated Preferir `openapi:sync`. Se mantiene como alias.
 */
class MergeAppSalesmanSwaggerCommand extends Command
{
    protected $signature = 'app-salesman:swagger-merge';

    protected $description = 'Alias de openapi:sync --scan=AppSalesman (mantener Swagger AppSalesman al día).';

    public function handle(): int
    {
        return $this->call('openapi:sync', [
            '--scan' => ['AppSalesman'],
            '--skip-full' => true,
        ]);
    }
}
