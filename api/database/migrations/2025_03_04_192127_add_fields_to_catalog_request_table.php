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
        Schema::table('catalog_request', function (Blueprint $table) {
            $table->unsignedBigInteger('catalog_id')->nullable();
            $table->integer('access_count')->default(0);
            $table->text('token')->nullable()->after('access_count');
            $table->foreign('catalog_id')->references('catalog_id')->on('catalog');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_request', function (Blueprint $table) {
            $table->dropForeign(['catalog_id']);
            $table->dropColumn(['catalog_id', 'access_count', 'token']);
        });
    }
};
