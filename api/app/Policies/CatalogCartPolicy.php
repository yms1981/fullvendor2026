<?php

namespace App\Policies;

use App\Models\CatalogCart;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CatalogCartPolicy
{
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
    public function view(User $user, CatalogCart $catalogCart): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $order = request()->input('order');
        $company_id = $order['company_id'];
        return $user->isSeller($company_id) || $user->isCustomer($company_id) || $user->isManager($company_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CatalogCart $catalogCart): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CatalogCart $catalogCart): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CatalogCart $catalogCart): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CatalogCart $catalogCart): bool
    {
        return $user->isAdmin();
    }
}
