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
        Schema::create('status_rol', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('company_id')->nullable();
            $table->integer('rol_id')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('update_at')->nullable()->useCurrent();
            $table->integer('id_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_rol');
    }
};
