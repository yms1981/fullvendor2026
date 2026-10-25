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
        Schema::create('companies_categories', function (Blueprint $table) {
            $table->bigInteger('category_id', true);
            $table->string('category_name')->nullable();
            $table->tinyInteger('category_status')->nullable()->default(1);
            $table->dateTime('category_created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies_categories');
    }
};
