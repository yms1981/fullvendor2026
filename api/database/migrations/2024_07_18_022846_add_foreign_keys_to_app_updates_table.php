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
        Schema::table('app_updates', function (Blueprint $table) {
            $table->foreign(['company_id'], 'app_updates_companies_company_id_fk')->references(['company_id'])->on('companies')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_updates', function (Blueprint $table) {
            $table->dropForeign('app_updates_companies_company_id_fk');
        });
    }
};
