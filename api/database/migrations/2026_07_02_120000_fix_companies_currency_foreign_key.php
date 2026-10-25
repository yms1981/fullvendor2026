<?php

use App\Support\SchemaMigration\LegacyTableMigrationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasTable('currency')) {
            return;
        }

        if (! Schema::hasColumn('companies', 'currency') || ! Schema::hasColumn('currency', 'id')) {
            return;
        }

        app(LegacyTableMigrationService::class)->migrateCompaniesCurrencyForeignKey();
    }

    public function down(): void
    {
        // No revertir automáticamente: el FK legacy currency_id no es compatible con el esquema nuevo.
    }
};
