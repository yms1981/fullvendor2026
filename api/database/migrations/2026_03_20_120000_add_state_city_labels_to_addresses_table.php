<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Texto libre cuando no hay coincidencia en catálogo (state_id / city_id nulos).
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('state_label', 255)->nullable()->after('state_id');
            $table->string('city_label', 255)->nullable()->after('city_id');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['state_label', 'city_label']);
        });
    }
};
