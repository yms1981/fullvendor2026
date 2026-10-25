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
        Schema::create('rol', function (Blueprint $table) {
            $table->integer('rol_id', true);
            $table->integer('company_id')->nullable();
            $table->string('name', 50)->nullable();
            $table->dateTime('created_date')->nullable()->useCurrent();
            $table->dateTime('modified_date')->nullable()->useCurrent();
            $table->integer('is_active')->nullable()->default(0);
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol');
    }
};
