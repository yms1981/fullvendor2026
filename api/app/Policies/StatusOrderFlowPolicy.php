<?php

namespace App\Policies;

use App\Models\StatusOrderFlow;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StatusOrderFlowPolicy
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
    public function view(User $user, StatusOrderFlow $statusOrderFlow): bool
    {
        return $user->isAdmin();
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
    public function update(User $user, StatusOrderFlow $statusOrderFlow): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StatusOrderFlow $statusOrderFlow): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StatusOrderFlow $statusOrderFlow): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StatusOrderFlow $statusOrderFlow): bool
    {
        return $user->isAdmin();
    }
}
