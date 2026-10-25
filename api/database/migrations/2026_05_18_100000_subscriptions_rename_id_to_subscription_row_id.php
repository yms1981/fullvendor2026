<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Alinea la PK con el CRUD admin: `subscription_row_id` (antes algunas instalaciones usaban `id`).
	 */
	public function up(): void
	{
		if (! Schema::hasTable('subscriptions')) {
			return;
		}
		if (Schema::hasColumn('subscriptions', 'subscription_row_id')) {
			return;
		}
		if (! Schema::hasColumn('subscriptions', 'id')) {
			return;
		}

		DB::statement('ALTER TABLE `subscriptions` CHANGE `id` `subscription_row_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
	}

	public function down(): void
	{
		if (! Schema::hasTable('subscriptions')) {
			return;
		}
		if (! Schema::hasColumn('subscriptions', 'subscription_row_id')) {
			return;
		}
		if (Schema::hasColumn('subscriptions', 'id')) {
			return;
		}

		DB::statement('ALTER TABLE `subscriptions` CHANGE `subscription_row_id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
	}
};
