<?php

namespace App\Policies;

use App\Models\FormatDate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FormatDatePolicy
{
    /**
     * Catálogo de formatos de fecha: lectura para perfiles de empresa (Company Profile).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isSeller() || $user->isWarehouse();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FormatDate $formatDate): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isSeller() || $user->isWarehouse();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FormatDate $formatDate): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FormatDate $formatDate): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FormatDate $formatDate): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FormatDate $formatDate): bool
    {
        return $user->isAdmin();
    }
}
