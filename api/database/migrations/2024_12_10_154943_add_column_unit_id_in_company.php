<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->autoIncrement()->change();
        });
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'default_unit_id')) {
                $table->foreignIdFor(\App\Models\Unit::class,'default_unit_id')
                    ->nullable()
                    ->references('unit_id')
                    ->on('units');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->integer('unit_id')->change();
        });
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
        });
    }
};
