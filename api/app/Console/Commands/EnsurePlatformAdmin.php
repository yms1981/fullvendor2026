<?php

namespace App\Console\Commands;

use App\Support\SchemaMigration\PlatformAdminService;
use Illuminate\Console\Command;
use Throwable;

class EnsurePlatformAdmin extends Command
{
    protected $signature = 'schema:ensure-admin
                            {--dry-run : Solo comprobar si falta el admin}';

    protected $description = 'Verifica y crea el usuario admin de /admin (admin@fullvendor.com) si no existe';

    public function handle(PlatformAdminService $platformAdmin): int
    {
        $audit = $platformAdmin->audit();

        if ($audit['exists']) {
            $this->info('Admin de plataforma OK.');
            $this->line("  id={$audit['admin_id']} email={$audit['email']} username={$audit['username']} user_id={$audit['user_id']}");

            return self::SUCCESS;
        }

        $this->warn('Admin de plataforma no disponible: ' . $audit['status']);

        if ($this->option('dry-run')) {
            $this->line('Ejecute schema:sync --force o schema:ensure-admin sin --dry-run para crearlo.');

            return self::FAILURE;
        }

        try {
            $result = $platformAdmin->ensure();
        } catch (Throwable $exception) {
            $this->error('Error al crear admin: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if ($result['created']) {
            $this->info('Admin de plataforma creado.');
        } else {
            $this->info('Admin de plataforma reparado/actualizado.');
        }

        $this->line("  id={$result['admin_id']} email={$result['email']} username={$result['username']} user_id={$result['user_id']}");
        $this->line('  Login /admin → usuario: ' . $result['username'] . ' | contraseña: ' . PlatformAdminService::DEFAULT_PASSWORD);

        return self::SUCCESS;
    }
}
