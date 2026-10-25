<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Aplica contraseña hasheada y en texto plano (users.password_plain) de forma consistente.
 */
class UserPasswordService
{
    /**
     * Asigna hash + texto plano sobre el modelo (sin guardar).
     */
    public function apply(User $user, ?string $plainPassword): void
    {
        $plain = trim((string) $plainPassword);
        if ($plain === '') {
            return;
        }

        $user->password = Hash::make($plain);
        if ($this->supportsPlainColumn()) {
            $user->password_plain = $plain;
        }
    }

    /**
     * Si password_plain está vacío y conocemos la contraseña en claro, la rellena y guarda.
     */
    public function ensurePlainFilled(User $user, ?string $plainPassword): void
    {
        if (! $this->supportsPlainColumn()) {
            return;
        }

        $plain = trim((string) $plainPassword);
        if ($plain === '') {
            return;
        }

        $current = trim((string) ($user->password_plain ?? ''));
        if ($current !== '') {
            return;
        }

        $user->password_plain = $plain;
        $user->saveQuietly();
    }

    /**
     * Devuelve atributos listos para mass-assignment / updateModel.
     *
     * @return array{password: string, password_plain?: string}|array{}
     */
    public function attributes(?string $plainPassword): array
    {
        $plain = trim((string) $plainPassword);
        if ($plain === '') {
            return [];
        }

        $attrs = [
            'password' => Hash::make($plain),
        ];
        if ($this->supportsPlainColumn()) {
            $attrs['password_plain'] = $plain;
        }

        return $attrs;
    }

    private function supportsPlainColumn(): bool
    {
        return Schema::hasColumn('users', 'password_plain');
    }
}
