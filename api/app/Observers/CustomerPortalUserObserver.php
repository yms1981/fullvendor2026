<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\EnsureCustomerPortalUserService;

/**
 * Tras crear/actualizar un cliente con email, asegura usuario portal en users.
 */
class CustomerPortalUserObserver
{
    public function __construct(
        private readonly EnsureCustomerPortalUserService $ensurePortalUser
    ) {}

    public function saved(Customer $customer): void
    {
        if (trim((string) ($customer->email ?? '')) === '') {
            return;
        }

        $fromRequest = trim((string) request()->input('portal_password', ''));
        $this->ensurePortalUser->ensure($customer, $fromRequest !== '' ? $fromRequest : null);
    }
}
