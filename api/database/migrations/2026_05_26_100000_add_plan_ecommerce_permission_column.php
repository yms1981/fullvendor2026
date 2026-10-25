<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasTable('plan') || Schema::hasColumn('plan', 'ecommerce_permission')) {
			return;
		}

		// BIT(1) para mantener coherencia con `plan.status`.
		DB::statement("ALTER TABLE `plan` ADD COLUMN `ecommerce_permission` BIT(1) NOT NULL DEFAULT b'0' AFTER `max_users`");
	}

	public function down(): void
	{
		if (! Schema::hasTable('plan') || ! Schema::hasColumn('plan', 'ecommerce_permission')) {
			return;
		}

		Schema::table('plan', function ($table) {
			$table->dropColumn('ecommerce_permission');
		});
	}
};
