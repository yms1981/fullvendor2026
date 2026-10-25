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
        Schema::create('status_cart', function (Blueprint $table) {
            $table->integer('id_status', true);
            $table->string('name_spa', 50)->nullable();
            $table->string('name_eng', 50)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('cod', 50)->nullable();
            $table->string('class', 50)->nullable();
            $table->string('selected_default', 50)->nullable();
            $table->integer('allow_toremove')->nullable()->default(0);
            $table->integer('multiple_cart')->nullable();
            $table->integer('nosubmit_order')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_cart');
    }
};
