<?php

namespace App\Console\Commands;

use App\Services\UserPasswordService;
use Illuminate\Console\Command;

class BackfillUserPasswordPlainCommand extends Command
{
    protected $signature = 'users:backfill-password-plain
                            {--dry-run : Solo reporta cuántos se podrían rellenar}';

    protected $description = 'Rellena users.password_plain cuando está vacío: legacy en claro o hash verificable con candidatas conocidas (bcrypt no se puede revertir).';

    public function handle(UserPasswordService $passwords): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Dry-run: no se escribirá en BD.');
        }

        $this->info('Backfill users.password_plain…');
        $stats = $passwords->backfillMissingPlains($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['scanned', $stats['scanned']],
                ['filled', $stats['filled']],
                ['skipped_already_plain', $stats['skipped']],
                ['unresolved', $stats['unresolved']],
            ]
        );

        if ($stats['unresolved'] > 0) {
            $this->warn('Los unresolved tienen hash bcrypt/argon sin candidata conocida; se rellenarán al próximo login correcto (ensurePlainFilled).');
        }

        return self::SUCCESS;
    }
}
