<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * PK de `plan`: subscription_id → plan_id (alineado con subscriptions.plan_id).
	 */
	public function up(): void
	{
		if (! Schema::hasTable('plan')) {
			return;
		}
		if (Schema::hasColumn('plan', 'subscription_id') && ! Schema::hasColumn('plan', 'plan_id')) {
			DB::statement('ALTER TABLE `plan` CHANGE `subscription_id` `plan_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
		}
	}

	public function down(): void
	{
		if (! Schema::hasTable('plan')) {
			return;
		}
		if (Schema::hasColumn('plan', 'plan_id') && ! Schema::hasColumn('plan', 'subscription_id')) {
			DB::statement('ALTER TABLE `plan` CHANGE `plan_id` `subscription_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
		}
	}
};
