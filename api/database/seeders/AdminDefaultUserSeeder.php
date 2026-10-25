<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuario administrador de plataforma: users + admin (login /admin con tabla admin).
 *
 * Uso: php artisan db:seed --class=AdminDefaultUserSeeder
 *
 * Variables opcionales en .env:
 *   ADMIN_SEED_EMAIL=admin@fullvendor.local
 *   ADMIN_SEED_NAME=Administrador
 *   ADMIN_SEED_USERNAME=admin
 *   ADMIN_SEED_PASSWORD=123456
 */
class AdminDefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_SEED_EMAIL', 'admin@fullvendor.local');
        $name = (string) (env('ADMIN_SEED_NAME', 'Administrador'));
        $username = (string) (env('ADMIN_SEED_USERNAME', 'admin'));
        $plain = (string) (env('ADMIN_SEED_PASSWORD', '123456'));

        $hash = Hash::make($plain);

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $hash,
            ]
        );

        Admin::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $email,
                'username' => $username,
                'password' => $hash,
                'status' => 1,
            ]
        );

        $this->command?->info("Admin listo: {$email} (contraseña: {$plain})");
    }
}
