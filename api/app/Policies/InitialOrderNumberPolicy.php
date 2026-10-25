<?php

namespace App\Policies;

use App\Models\InitialOrderNumber;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InitialOrderNumberPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, InitialOrderNumber $initialOrderNumber): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, InitialOrderNumber $initialOrderNumber): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, InitialOrderNumber $initialOrderNumber): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, InitialOrderNumber $initialOrderNumber): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, InitialOrderNumber $initialOrderNumber): bool
    {
        return $user->isAdmin();
    }
}
