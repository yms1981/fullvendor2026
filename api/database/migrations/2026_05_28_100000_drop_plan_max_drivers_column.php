<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina la columna legada plan.max_Drivers.
     */
    public function up(): void
    {
        if (! Schema::hasTable('plan')) {
            return;
        }

        Schema::table('plan', function (Blueprint $table) {
            if (Schema::hasColumn('plan', 'max_Drivers')) {
                $table->dropColumn('max_Drivers');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('plan')) {
            return;
        }

        Schema::table('plan', function (Blueprint $table) {
            if (! Schema::hasColumn('plan', 'max_Drivers')) {
                $table->unsignedInteger('max_Drivers')->nullable()->default(0);
            }
        });
    }
};
