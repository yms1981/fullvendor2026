<?php

namespace App\Policies;

use App\Models\ImportHistory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ImportHistoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImportHistory $importHistory): bool
    {
        return $user->isAdmin() || $user->isManager((int) $importHistory->company_id);
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
    public function update(User $user, ImportHistory $importHistory): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImportHistory $importHistory): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ImportHistory $importHistory): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ImportHistory $importHistory): bool
    {
        return $user->isAdmin();
    }
}
