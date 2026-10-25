<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasTable('invoices') || Schema::hasColumn('invoices', 'entidad_id')) {
			return;
		}

		Schema::table('invoices', function (Blueprint $table) {
			$table->unsignedBigInteger('entidad_id')->nullable()->after('days');
			$table->index('entidad_id', 'invoices_entidad_id');
		});
	}

	public function down(): void
	{
		if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'entidad_id')) {
			return;
		}

		Schema::table('invoices', function (Blueprint $table) {
			$table->dropIndex('invoices_entidad_id');
			$table->dropColumn('entidad_id');
		});
	}
};
