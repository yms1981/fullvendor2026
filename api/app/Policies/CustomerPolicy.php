<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    private function sellerCanAccessCustomerInCompany(User $user, Customer $customer): bool
    {
        $companyId = (int) $customer->company_id;
        if ($companyId <= 0 || ! $user->isSeller($companyId)) {
            return false;
        }
        $vendor = $user->sellerByCompany($companyId);
        if (! $vendor) {
            return false;
        }
        if ((int) ($vendor->all_customers ?? 0) === 1) {
            return true;
        }

        return $customer->sellers()->where('sellers.id', $vendor->id)->exists();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isManager((int) $customer->company_id)) {
            return true;
        }

        return $this->sellerCanAccessCustomerInCompany($user, $customer);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isSeller();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): bool
    {
        if ($user->isAdmin() || $user->isManager((int) $customer->company_id)) {
            return true;
        }

        return $this->sellerCanAccessCustomerInCompany($user, $customer);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function bulkDelete(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
