<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Estado del plan como bit/tinyint: 1 = habilitado, 0 = deshabilitado.
	 * Convierte valores VARCHAR heredados (active, cancelled, expired, enabled, disabled, …).
	 */
	public function up(): void
	{
		if (! Schema::hasTable('plan') || ! Schema::hasColumn('plan', 'status')) {
			return;
		}

		$cols = DB::select("SHOW COLUMNS FROM `plan` WHERE Field = 'status'");
		if ($cols === []) {
			return;
		}
		$type = strtolower((string) ($cols[0]->Type ?? ''));
		if (str_contains($type, 'tinyint') || str_contains($type, 'bit')) {
			return;
		}

		DB::statement('ALTER TABLE `plan` ADD COLUMN `status_tmp` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER `billing_period`');
		DB::statement(
			"UPDATE `plan` SET `status_tmp` = IF(LOWER(TRIM(CAST(`status` AS CHAR))) IN ('1','active','enabled'), 1, 0)"
		);
		DB::statement('ALTER TABLE `plan` DROP COLUMN `status`');
		DB::statement('ALTER TABLE `plan` CHANGE `status_tmp` `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1');
	}

	public function down(): void
	{
		if (! Schema::hasTable('plan') || ! Schema::hasColumn('plan', 'status')) {
			return;
		}

		$cols = DB::select("SHOW COLUMNS FROM `plan` WHERE Field = 'status'");
		if ($cols === []) {
			return;
		}
		$type = strtolower((string) ($cols[0]->Type ?? ''));
		if (! str_contains($type, 'tinyint') && ! str_contains($type, 'bit')) {
			return;
		}

		DB::statement('ALTER TABLE `plan` ADD COLUMN `status_tmp` VARCHAR(20) NOT NULL DEFAULT \'active\' AFTER `billing_period`');
		DB::statement("UPDATE `plan` SET `status_tmp` = IF(`status` = 1, 'active', 'cancelled')");
		DB::statement('ALTER TABLE `plan` DROP COLUMN `status`');
		DB::statement('ALTER TABLE `plan` CHANGE `status_tmp` `status` VARCHAR(20) NOT NULL DEFAULT \'active\'');
	}
};
