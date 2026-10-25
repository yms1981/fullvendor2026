<?php

namespace App\Services\AppSalesman;

use App\Services\CustomerOrderProductFifoService;

/**
 * Listados AppSalesman FIFO (detalle por orden y resumen por cliente+sku).
 */
class AppSalesmanProductFifoService
{
    public function __construct(
        private readonly CustomerOrderProductFifoService $fifoService,
    ) {
    }

    /**
     * @return array{status: string, company_id?: int, items?: list<array<string, mixed>>, error?: string}
     */
    public function listForCompany(int $companyId, bool $onlyAvailable = true): array
    {
        return $this->fifoService->listForCompany($companyId, $onlyAvailable);
    }

    /**
     * @return array{status: string, company_id?: int, items?: list<array<string, mixed>>, error?: string}
     */
    public function summaryByCustomerSku(int $companyId, bool $onlyAvailable = true): array
    {
        return $this->fifoService->summaryByCustomerSku($companyId, $onlyAvailable);
    }
}
