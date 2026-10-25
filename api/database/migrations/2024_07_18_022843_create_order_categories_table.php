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
        Schema::create('order_categories', function (Blueprint $table) {
            $table->integer('order_category_id', true);
            $table->integer('category_id')->nullable();
            $table->integer('catalog_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('order')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_categories');
    }
};
