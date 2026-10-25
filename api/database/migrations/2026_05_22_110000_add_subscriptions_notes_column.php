<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Notas internas en suscripciones (CRUD admin).
	 */
	public function up(): void
	{
		if (! Schema::hasTable('subscriptions')) {
			return;
		}
		if (Schema::hasColumn('subscriptions', 'notes')) {
			return;
		}
		Schema::table('subscriptions', function (Blueprint $table) {
			$table->text('notes')->nullable();
		});
	}

	public function down(): void
	{
		if (! Schema::hasTable('subscriptions')) {
			return;
		}
		if (! Schema::hasColumn('subscriptions', 'notes')) {
			return;
		}
		Schema::table('subscriptions', function (Blueprint $table) {
			$table->dropColumn('notes');
		});
	}
};
