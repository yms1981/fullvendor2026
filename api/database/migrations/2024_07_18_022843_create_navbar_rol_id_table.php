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
        Schema::create('navbar_rol_id', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('navbar_id')->nullable();
            $table->integer('rol_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('fhater_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navbar_rol_id');
    }
};
