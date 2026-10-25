<?php

namespace App\Support\SchemaMigration;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PlatformAdminService
{
    public const DEFAULT_EMAIL = 'admin@fullvendor.com';

    public const DEFAULT_USERNAME = 'admin';

    public const DEFAULT_PASSWORD = '123456';

    public const DEFAULT_NAME = 'Administrador';

    /**
     * @return array{exists: bool, admin_id: int|null, email: string|null, username: string|null, user_id: int|null, status: string}
     */
    public function audit(): array
    {
        if (! Schema::hasTable('admin')) {
            return [
                'exists' => false,
                'admin_id' => null,
                'email' => null,
                'username' => null,
                'user_id' => null,
                'status' => 'missing_table',
            ];
        }

        $admin = $this->findPlatformAdmin();

        if ($admin === null) {
            return [
                'exists' => false,
                'admin_id' => null,
                'email' => null,
                'username' => null,
                'user_id' => null,
                'status' => 'missing',
            ];
        }

        $userOk = $admin->user_id !== null
            && Schema::hasTable('users')
            && User::query()->whereKey($admin->user_id)->exists();

        if (! $userOk) {
            return [
                'exists' => false,
                'admin_id' => $admin->id,
                'email' => $admin->email,
                'username' => $admin->username,
                'user_id' => $admin->user_id,
                'status' => 'invalid_user_link',
            ];
        }

        if ($admin->status !== null && (int) $admin->status === 0) {
            return [
                'exists' => false,
                'admin_id' => $admin->id,
                'email' => $admin->email,
                'username' => $admin->username,
                'user_id' => $admin->user_id,
                'status' => 'inactive',
            ];
        }

        return [
            'exists' => true,
            'admin_id' => $admin->id,
            'email' => $admin->email,
            'username' => $admin->username,
            'user_id' => $admin->user_id,
            'status' => 'ok',
        ];
    }

    public function needsBootstrap(): bool
    {
        // Tras migrate:fresh las tablas aún no existen: marcar bootstrap y crearlo
        // después de schema:sync (apply), no fallar al analizar el plan.
        if (! Schema::hasTable('admin') || ! Schema::hasTable('users')) {
            return true;
        }

        return ! $this->audit()['exists'];
    }

    /**
     * @return array{created: bool, admin_id: int, user_id: int, email: string, username: string}
     */
    public function ensure(): array
    {
        if (! Schema::hasTable('admin') || ! Schema::hasTable('users')) {
            throw new \RuntimeException('Las tablas admin y users deben existir antes de crear el administrador de plataforma.');
        }

        $existing = $this->findPlatformAdmin();
        if ($existing !== null && $this->audit()['exists']) {
            return [
                'created' => false,
                'admin_id' => $existing->id,
                'user_id' => (int) $existing->user_id,
                'email' => (string) ($existing->email ?? self::DEFAULT_EMAIL),
                'username' => (string) ($existing->username ?? self::DEFAULT_USERNAME),
            ];
        }

        $email = (string) env('PLATFORM_ADMIN_EMAIL', self::DEFAULT_EMAIL);
        $username = (string) env('PLATFORM_ADMIN_USERNAME', self::DEFAULT_USERNAME);
        $password = (string) env('PLATFORM_ADMIN_PASSWORD', self::DEFAULT_PASSWORD);
        $name = (string) env('PLATFORM_ADMIN_NAME', self::DEFAULT_NAME);

        return DB::transaction(function () use ($existing, $email, $username, $password, $name): array {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'password_plain' => $password,
                    'language_id' => $this->defaultLanguageId(),
                ]);
            }

            if ($existing !== null) {
                $existing->forceFill([
                    'user_id' => $user->id,
                    'email' => $email,
                    'username' => $username,
                    'password' => $password,
                    'status' => 1,
                ])->save();

                return [
                    'created' => false,
                    'admin_id' => $existing->id,
                    'user_id' => $user->id,
                    'email' => $email,
                    'username' => $username,
                ];
            }

            $admin = Admin::query()->create([
                'user_id' => $user->id,
                'email' => $email,
                'username' => $username,
                'password' => $password,
                'status' => 1,
                'created' => now(),
            ]);

            return [
                'created' => true,
                'admin_id' => $admin->id,
                'user_id' => $user->id,
                'email' => $email,
                'username' => $username,
            ];
        });
    }

    private function findPlatformAdmin(): ?Admin
    {
        $email = (string) env('PLATFORM_ADMIN_EMAIL', self::DEFAULT_EMAIL);
        $username = (string) env('PLATFORM_ADMIN_USERNAME', self::DEFAULT_USERNAME);

        return Admin::query()
            ->where(function ($query) use ($email, $username): void {
                $query->where('email', $email)
                    ->orWhere('username', $username)
                    ->orWhere('username', $email);
            })
            ->orderBy('id')
            ->first();
    }

    private function defaultLanguageId(): int
    {
        if (! Schema::hasTable('languages')) {
            return 1;
        }

        $language = DB::table('languages')
            ->where('status', 1)
            ->orderBy('language_id')
            ->value('language_id');

        return (int) ($language ?? 1);
    }
}
