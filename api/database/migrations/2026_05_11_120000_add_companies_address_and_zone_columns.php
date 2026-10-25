<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dirección de la empresa: campos simples en `companies` (full_address, address, country, state, city, zip_code, zone).
     * Reintroduce columnas quitadas en 2025_06_02; sin duplicar variantes tipo comercial/despacho.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'full_address')) {
                $table->text('full_address')->nullable();
            }
            if (! Schema::hasColumn('companies', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('companies', 'country')) {
                $table->string('country', 191)->nullable();
            }
            if (! Schema::hasColumn('companies', 'state')) {
                $table->string('state', 191)->nullable();
            }
            if (! Schema::hasColumn('companies', 'city')) {
                $table->string('city', 191)->nullable();
            }
            if (! Schema::hasColumn('companies', 'zip_code')) {
                $table->string('zip_code', 191)->nullable();
            }
            if (! Schema::hasColumn('companies', 'zone')) {
                $table->string('zone', 191)->nullable();
            }
        });
    }

    public function down(): void
    {
        $toDrop = [];
        foreach (['zone', 'zip_code', 'city', 'state', 'country', 'address', 'full_address'] as $col) {
            if (Schema::hasColumn('companies', $col)) {
                $toDrop[] = $col;
            }
        }
        if ($toDrop === []) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) use ($toDrop) {
            $table->dropColumn($toDrop);
        });
    }
};
