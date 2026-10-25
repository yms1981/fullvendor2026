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
        Schema::create('company_status_order', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cod_status')->nullable()->default(0);
            $table->integer('company_id')->nullable();
            $table->string('name', 50)->nullable();
            $table->longText('email')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_status_order');
    }
};
