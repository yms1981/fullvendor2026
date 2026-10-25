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

		if (Schema::hasColumn('companies', 'whatsapp_instance_name')) {
			return;
		}

		// El flag "Use WhatsApp" puede llamarse `use_whatsapp` (esquema actual) o `whatsapp_instance` (esquema legacy
		// antes del rename); usamos el que exista para el AFTER.
		if (Schema::hasColumn('companies', 'use_whatsapp')) {
			DB::statement("ALTER TABLE `companies` ADD COLUMN `whatsapp_instance_name` VARCHAR(255) NULL DEFAULT NULL AFTER `use_whatsapp`");
		} elseif (Schema::hasColumn('companies', 'whatsapp_instance')) {
			DB::statement("ALTER TABLE `companies` ADD COLUMN `whatsapp_instance_name` VARCHAR(255) NULL DEFAULT NULL AFTER `whatsapp_instance`");
		} else {
			DB::statement("ALTER TABLE `companies` ADD COLUMN `whatsapp_instance_name` VARCHAR(255) NULL DEFAULT NULL");
		}
	}

	public function down(): void
	{
		if (! Schema::hasTable('companies')) {
			return;
		}

		if (Schema::hasColumn('companies', 'whatsapp_instance_name')) {
			Schema::table('companies', function ($table) {
				$table->dropColumn('whatsapp_instance_name');
			});
		}
	}
};
