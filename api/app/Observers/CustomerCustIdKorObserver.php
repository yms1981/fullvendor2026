<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\CustomerCustIdKorService;

/**
 * Garantiza cust_id_kor: CXXXXXXX en altas; customer_id si queda vacío en existentes.
 */
class CustomerCustIdKorObserver
{
    public function __construct(
        private readonly CustomerCustIdKorService $korService,
    ) {
    }

    public function creating(Customer $customer): void
    {
        if ($this->korService->isBlank($customer->cust_id_kor ?? null)) {
            $customer->cust_id_kor = $this->korService->generateUnique();
        }
    }

    public function saving(Customer $customer): void
    {
        if ($customer->exists) {
            $this->korService->fillIfBlankWithCustomerId($customer);
        }
    }

    public function created(Customer $customer): void
    {
        $this->korService->fillAfterCreate($customer);
    }
}
