<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columna `full_address` si la migración 2026_05_11 ya se aplicó antes de incluir este campo.
     */
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'full_address')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            $table->text('full_address')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'full_address')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('full_address');
        });
    }
};
