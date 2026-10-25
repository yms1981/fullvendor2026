<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasTable('companies')) {
			return;
		}

		// Si ya existe el nombre nuevo no hacemos nada.
		if (Schema::hasColumn('companies', 'use_whatsapp')) {
			return;
		}

		if (Schema::hasColumn('companies', 'whatsapp_instance')) {
			DB::statement("ALTER TABLE `companies` CHANGE COLUMN `whatsapp_instance` `use_whatsapp` BIT(1) NOT NULL DEFAULT b'0'");

			return;
		}

		// La tabla todavía no tiene la columna: la creamos directamente con el nombre nuevo.
		DB::statement("ALTER TABLE `companies` ADD COLUMN `use_whatsapp` BIT(1) NOT NULL DEFAULT b'0' AFTER `show_semaforo_stock`");
	}

	public function down(): void
	{
		if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'use_whatsapp')) {
			return;
		}

		if (Schema::hasColumn('companies', 'whatsapp_instance')) {
			Schema::table('companies', function ($table) {
				$table->dropColumn('use_whatsapp');
			});

			return;
		}

		DB::statement("ALTER TABLE `companies` CHANGE COLUMN `use_whatsapp` `whatsapp_instance` BIT(1) NOT NULL DEFAULT b'0'");
	}
};
