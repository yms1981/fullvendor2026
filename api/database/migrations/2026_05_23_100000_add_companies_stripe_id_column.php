<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasTable('companies')) {
			return;
		}
		if (Schema::hasColumn('companies', 'stripe_id')) {
			return;
		}
		Schema::table('companies', function (Blueprint $table) {
			$table->string('stripe_id', 255)->nullable()->after('email');
		});
	}

	public function down(): void
	{
		if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'stripe_id')) {
			return;
		}
		Schema::table('companies', function (Blueprint $table) {
			$table->dropColumn('stripe_id');
		});
	}
};
