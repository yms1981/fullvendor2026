<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Plan: documentos Stripe (homólogo a Facturas legado).
	 */
	public function up(): void
	{
		if (! Schema::hasTable('plan')) {
			return;
		}

		Schema::table('plan', function (Blueprint $table) {
			if (! Schema::hasColumn('plan', 'stripe_doc')) {
				$table->text('stripe_doc')->nullable()->after('stripeName');
			}
			if (! Schema::hasColumn('plan', 'stripe_url')) {
				$table->text('stripe_url')->nullable()->after('stripe_doc');
			}
			if (! Schema::hasColumn('plan', 'stripe_pdf')) {
				$table->text('stripe_pdf')->nullable()->after('stripe_url');
			}
		});
	}

	public function down(): void
	{
		if (! Schema::hasTable('plan')) {
			return;
		}

		Schema::table('plan', function (Blueprint $table) {
			foreach (['stripe_doc', 'stripe_url', 'stripe_pdf'] as $col) {
				if (Schema::hasColumn('plan', $col)) {
					$table->dropColumn($col);
				}
			}
		});
	}
};
