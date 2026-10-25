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
        Schema::create('status_orders', function (Blueprint $table) {
            $table->integer('id_status', true);
            $table->string('name_status_spanish')->nullable();
            $table->string('name_status_english')->nullable();
            $table->integer('cod_status')->nullable();
            $table->string('color')->nullable();
            $table->integer('disabled')->nullable();
            $table->string('sombrilla')->nullable();
            $table->string('color_icono', 50)->nullable();
            $table->string('tipo_d', 50)->nullable();
            $table->integer('cannot_edit')->nullable();
            $table->integer('can_show_data_asigned')->nullable();
            $table->integer('send_email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_orders');
    }
};
