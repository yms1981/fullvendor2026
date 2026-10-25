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

		// BIT(1) para mantener coherencia con `companies.status` / `plan.ecommerce_permission`.
		// Si ya existe `whatsapp_instance` (de una versión anterior de esta migración) la migración
		// de rename `..._rename_companies_whatsapp_instance_to_use_whatsapp` se encarga de pasarla a `use_whatsapp`.
		if (! Schema::hasColumn('companies', 'use_whatsapp') && ! Schema::hasColumn('companies', 'whatsapp_instance')) {
			DB::statement("ALTER TABLE `companies` ADD COLUMN `use_whatsapp` BIT(1) NOT NULL DEFAULT b'0' AFTER `show_semaforo_stock`");
		}

		if (! Schema::hasColumn('companies', 'whatsapp_apikey')) {
			$afterCol = Schema::hasColumn('companies', 'use_whatsapp') ? 'use_whatsapp' : 'whatsapp_instance';
			DB::statement("ALTER TABLE `companies` ADD COLUMN `whatsapp_apikey` VARCHAR(255) NULL DEFAULT NULL AFTER `" . $afterCol . "`");
		}
	}

	public function down(): void
	{
		if (! Schema::hasTable('companies')) {
			return;
		}

		if (Schema::hasColumn('companies', 'whatsapp_apikey')) {
			Schema::table('companies', function ($table) {
				$table->dropColumn('whatsapp_apikey');
			});
		}

		if (Schema::hasColumn('companies', 'use_whatsapp')) {
			Schema::table('companies', function ($table) {
				$table->dropColumn('use_whatsapp');
			});
		}

		if (Schema::hasColumn('companies', 'whatsapp_instance')) {
			Schema::table('companies', function ($table) {
				$table->dropColumn('whatsapp_instance');
			});
		}
	}
};
