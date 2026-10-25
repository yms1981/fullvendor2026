<?php

namespace App\Console\Commands;

use App\Services\CustomerOrderProductFifoService;
use Illuminate\Console\Command;

class RebuildCustomerOrderProductFifoCommand extends Command
{
    protected $signature = 'fifo:rebuild
                            {--company= : company_id opcional}
                            {--customer= : customer_id opcional}';

    protected $description = 'Reconstruye la tabla FIFO de ventas/NC por cliente-producto-orden.';

    public function handle(CustomerOrderProductFifoService $fifo): int
    {
        if (! $fifo->tablesReady()) {
            $this->error('Tablas FIFO no existen. Aplique schema:sync o el SQL 2026_10_23_customer_order_product_fifo.sql');

            return self::FAILURE;
        }

        $companyId = $this->option('company') !== null && $this->option('company') !== ''
            ? (int) $this->option('company')
            : null;
        $customerId = $this->option('customer') !== null && $this->option('customer') !== ''
            ? (int) $this->option('customer')
            : null;

        $this->info('Reconstruyendo FIFO…');
        $result = $fifo->rebuild($companyId, $customerId);

        if (isset($result['error'])) {
            $this->error((string) $result['error']);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'OK: %d órdenes venta sincronizadas, %d notas de crédito aplicadas.',
            (int) ($result['layers'] ?? 0),
            (int) ($result['credits'] ?? 0)
        ));

        return self::SUCCESS;
    }
}
