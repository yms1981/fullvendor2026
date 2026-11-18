<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Aplica contraseña hasheada y en texto plano (users.password_plain) de forma consistente.
 *
 * Nota: un hash bcrypt/argon NO se puede revertir. Solo se puede recuperar el plano
 * si (a) ya está en password_plain, (b) el login aporta el texto, o (c) Hash::check
 * confirma una candidata conocida.
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
     * Intenta recuperar/rellenar password_plain a partir del hash (candidatas) o de un valor legacy en claro.
     */
    public function recoverPlainIfPossible(User $user, array $extraCandidates = []): ?string
    {
        if (! $this->supportsPlainColumn()) {
            return null;
        }

        $current = trim((string) ($user->password_plain ?? ''));
        if ($current !== '') {
            return $current;
        }

        $stored = (string) ($user->password ?? '');
        if ($stored === '') {
            return null;
        }

        // Legacy: password guardado en claro (no hash).
        if (! $this->isHashedPassword($stored)) {
            $plain = trim($stored);
            if ($plain === '') {
                return null;
            }
            $user->password_plain = $plain;
            $user->password = Hash::make($plain);
            $user->saveQuietly();

            return $plain;
        }

        foreach ($this->candidatePlains($extraCandidates) as $candidate) {
            if (Hash::check($candidate, $stored)) {
                $user->password_plain = $candidate;
                $user->saveQuietly();

                return $candidate;
            }
        }

        return null;
    }

    /**
     * Backfill masivo de users.password_plain.
     *
     * @return array{scanned:int,filled:int,skipped:int,unresolved:int}
     */
    public function backfillMissingPlains(bool $dryRun = false): array
    {
        $stats = [
            'scanned' => 0,
            'filled' => 0,
            'skipped' => 0,
            'unresolved' => 0,
        ];

        if (! $this->supportsPlainColumn()) {
            return $stats;
        }

        // Priorizar candidatas frecuentes (bcrypt es costoso).
        $priority = ['12345678', '123456', '123456789', 'password', 'Password1', 'admin', 'Admin123'];
        $fromDb = DB::table('users')
            ->select('password_plain', DB::raw('COUNT(*) as c'))
            ->whereNotNull('password_plain')
            ->where('password_plain', '!=', '')
            ->groupBy('password_plain')
            ->orderByDesc('c')
            ->limit(30)
            ->pluck('password_plain')
            ->map(static fn ($v) => trim((string) $v))
            ->filter(static fn ($v) => $v !== '')
            ->values()
            ->all();
        $knownPlains = [];
        foreach (array_merge($priority, $fromDb) as $plain) {
            $plain = trim((string) $plain);
            if ($plain !== '') {
                $knownPlains[$plain] = $plain;
            }
        }
        $knownPlains = array_values($knownPlains);

        User::query()
            ->orderBy('id')
            ->chunkById(50, function ($users) use (&$stats, $knownPlains, $dryRun) {
                foreach ($users as $user) {
                    $stats['scanned']++;
                    $current = trim((string) ($user->password_plain ?? ''));
                    if ($current !== '') {
                        $stats['skipped']++;
                        continue;
                    }

                    $stored = (string) ($user->password ?? '');
                    if ($stored === '') {
                        $stats['unresolved']++;
                        continue;
                    }

                    $recovered = null;
                    if (! $this->isHashedPassword($stored)) {
                        $recovered = trim($stored);
                    } else {
                        foreach ($knownPlains as $candidate) {
                            if (Hash::check($candidate, $stored)) {
                                $recovered = $candidate;
                                break;
                            }
                        }
                    }

                    if ($recovered === null || $recovered === '') {
                        $stats['unresolved']++;
                        continue;
                    }

                    if (! $dryRun) {
                        if (! $this->isHashedPassword($stored)) {
                            $user->password = Hash::make($recovered);
                        }
                        $user->password_plain = $recovered;
                        $user->saveQuietly();
                    }
                    $stats['filled']++;
                }
            });

        return $stats;
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

    public function isHashedPassword(?string $value): bool
    {
        $v = trim((string) $value);
        if ($v === '') {
            return false;
        }

        return str_starts_with($v, '$2y$')
            || str_starts_with($v, '$2a$')
            || str_starts_with($v, '$2b$')
            || str_starts_with($v, '$argon2i$')
            || str_starts_with($v, '$argon2id$');
    }

    /**
     * @param  list<string>  $extra
     * @return list<string>
     */
    public function candidatePlains(array $extra = []): array
    {
        $defaults = [
            '12345678',
            '123456',
            '123456789',
            'password',
            'Password1',
            'admin',
            'Admin123',
            'fullvendor',
            'Fullvendor1',
        ];

        $fromDb = [];
        if ($this->supportsPlainColumn()) {
            $fromDb = DB::table('users')
                ->whereNotNull('password_plain')
                ->where('password_plain', '!=', '')
                ->distinct()
                ->orderBy('password_plain')
                ->limit(500)
                ->pluck('password_plain')
                ->map(static fn ($v) => trim((string) $v))
                ->filter(static fn ($v) => $v !== '')
                ->values()
                ->all();
        }

        $merged = [];
        foreach (array_merge($defaults, $fromDb, $extra) as $plain) {
            $plain = trim((string) $plain);
            if ($plain === '') {
                continue;
            }
            $merged[$plain] = $plain;
        }

        return array_values($merged);
    }

    private function supportsPlainColumn(): bool
    {
        return Schema::hasColumn('users', 'password_plain');
    }
}
