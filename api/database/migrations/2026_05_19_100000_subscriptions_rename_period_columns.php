<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Renombra fechas de periodo a start_period_date / end_period_date (alineado con BD real).
	 */
	public function up(): void
	{
		if (! Schema::hasTable('subscriptions')) {
			return;
		}
		if (Schema::hasColumn('subscriptions', 'start_period_date')) {
			return;
		}
		if (Schema::hasColumn('subscriptions', 'starts_at')) {
			DB::statement('ALTER TABLE `subscriptions` CHANGE `starts_at` `start_period_date` DATE NULL DEFAULT NULL');
		}
		if (Schema::hasColumn('subscriptions', 'ends_at')) {
			DB::statement('ALTER TABLE `subscriptions` CHANGE `ends_at` `end_period_date` DATE NULL DEFAULT NULL');
		}
	}

	public function down(): void
	{
		if (! Schema::hasTable('subscriptions')) {
			return;
		}
		if (Schema::hasColumn('subscriptions', 'starts_at')) {
			return;
		}
		if (Schema::hasColumn('subscriptions', 'start_period_date')) {
			DB::statement('ALTER TABLE `subscriptions` CHANGE `start_period_date` `starts_at` DATE NULL DEFAULT NULL');
		}
		if (Schema::hasColumn('subscriptions', 'end_period_date')) {
			DB::statement('ALTER TABLE `subscriptions` CHANGE `end_period_date` `ends_at` DATE NULL DEFAULT NULL');
		}
	}
};
