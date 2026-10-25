<?php

namespace App\Policies;

use App\Models\EntidadEmisora;
use App\Models\User;

class EntidadEmisoraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, EntidadEmisora $entidadEmisora): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, EntidadEmisora $entidadEmisora): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, EntidadEmisora $entidadEmisora): bool
    {
        return $user->isAdmin();
    }
}
