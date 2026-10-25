<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Renombra subscriptions → plan y lifecycle_status → status (MySQL).
	 */
	public function up(): void
	{
		if (Schema::hasTable('subscriptions') && ! Schema::hasTable('plan')) {
			DB::statement('RENAME TABLE `subscriptions` TO `plan`');
		}
		if (Schema::hasTable('plan') && Schema::hasColumn('plan', 'lifecycle_status') && ! Schema::hasColumn('plan', 'status')) {
			DB::statement('ALTER TABLE `plan` CHANGE `lifecycle_status` `status` VARCHAR(20) NOT NULL');
		}
	}

	public function down(): void
	{
		if (! Schema::hasTable('plan')) {
			return;
		}
		if (Schema::hasColumn('plan', 'status') && ! Schema::hasColumn('plan', 'lifecycle_status')) {
			DB::statement('ALTER TABLE `plan` CHANGE `status` `lifecycle_status` VARCHAR(20) NOT NULL');
		}
		if (Schema::hasTable('plan') && ! Schema::hasTable('subscriptions')) {
			DB::statement('RENAME TABLE `plan` TO `subscriptions`');
		}
	}
};
