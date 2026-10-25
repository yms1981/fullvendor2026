<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Sustituye language_id por language1_id y language2_id en plan.
	 */
	public function up(): void
	{
		if (! Schema::hasTable('plan')) {
			return;
		}
		$afterPk = Schema::hasColumn('plan', 'plan_id') ? 'plan_id' : 'subscription_id';
		if (! Schema::hasColumn('plan', 'language1_id')) {
			Schema::table('plan', function (Blueprint $table) use ($afterPk) {
				$table->integer('language1_id')->default(0)->after($afterPk);
			});
		}
		if (! Schema::hasColumn('plan', 'language2_id')) {
			Schema::table('plan', function (Blueprint $table) {
				$table->integer('language2_id')->default(0)->after('language1_id');
			});
		}
		if (Schema::hasColumn('plan', 'language_id')) {
			DB::statement('UPDATE `plan` SET `language1_id` = `language_id` WHERE `language_id` > 0');
			Schema::table('plan', function (Blueprint $table) {
				$table->dropColumn('language_id');
			});
		}
	}

	public function down(): void
	{
		if (! Schema::hasTable('plan')) {
			return;
		}
		$afterPk = Schema::hasColumn('plan', 'plan_id') ? 'plan_id' : 'subscription_id';
		if (! Schema::hasColumn('plan', 'language_id')) {
			Schema::table('plan', function (Blueprint $table) use ($afterPk) {
				$table->integer('language_id')->default(0)->after($afterPk);
			});
			DB::statement('UPDATE `plan` SET `language_id` = `language1_id` WHERE `language1_id` > 0');
		}
		if (Schema::hasColumn('plan', 'language2_id')) {
			Schema::table('plan', function (Blueprint $table) {
				$table->dropColumn('language2_id');
			});
		}
		if (Schema::hasColumn('plan', 'language1_id')) {
			Schema::table('plan', function (Blueprint $table) {
				$table->dropColumn('language1_id');
			});
		}
	}
};
