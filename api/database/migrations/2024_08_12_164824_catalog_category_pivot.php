<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalog_category', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Catalog::class, 'catalog_id')
                ->references('catalog_id')->on('catalog');
            $table->foreignIdFor(\App\Models\Category::class,'category_id')
                ->references('cat_id')->on('categories');
            $table->smallInteger('order')->unsigned();


            if (!Schema::hasColumn('catalog_category', 'created_at')) {
                $table->timestamps();
            }
            if (!Schema::hasColumn('catalog_category', 'deleted_at')) {
                $table->softDeletes();
            }


            $table->unique(['category_id', 'catalog_id'], 'unique_category_catalog');
            $table->unique(['order', 'catalog_id'], 'unique_catalog_order');
            $table->primary(['catalog_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_category');
    }
};
