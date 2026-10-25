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
        Schema::create('catalog_product', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Catalog::class,'catalog_id')
                ->references('catalog_id')->on('catalog')
                ->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Product::class,'product_id')
                ->references('pro_id')->on('products')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            //$table->primary(['catalog_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_product');
    }
};
