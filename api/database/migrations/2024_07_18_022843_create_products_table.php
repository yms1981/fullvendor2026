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
        Schema::create('products', function (Blueprint $table) {
            $table->bigInteger('pro_id', true);
            $table->bigInteger('product_id')->nullable();
            $table->integer('language_id')->default(1);
            $table->bigInteger('company_id')->nullable();
            $table->bigInteger('warehouse_user_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('name')->nullable();
            $table->double('sale_price');
            $table->string('category_id')->nullable()->comment('PK of categories');
            $table->string('barcode')->nullable();
            $table->string('barcode_images')->nullable();
            $table->text('tags')->nullable();
            $table->text('descriptions')->nullable();
            $table->string('unit_type', 50)->nullable();
            $table->double('stock')->default(0);
            $table->double('minimum_stock')->default(1);
            $table->tinyInteger('notify_minimum_stock')->default(1)->comment('1-Yes 0-No');
            $table->string('currency_type')->nullable();
            $table->double('fob_price');
            $table->double('purchase_price');
            $table->string('locations')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->timestamp('modified_on')->useCurrentOnUpdate()->useCurrent();
            $table->tinyInteger('status')->comment('0-InActive 1-Active');
            $table->binary('Img')->nullable();
            $table->longText('imgURL')->nullable();
            $table->longText('simgURL')->nullable();
            $table->bigInteger('id_kor')->nullable();
            $table->longText('cat_id')->nullable();
            $table->longText('cats_id')->nullable();
            $table->integer('sell_with_inventory')->nullable()->default(0);
            $table->integer('image_updated')->nullable()->default(0);
            $table->double('price_reduced')->default(0);
            $table->integer('sem_red_hasta')->nullable();
            $table->integer('sem_amarillo_desde')->nullable();
            $table->integer('sem_amarillo_hasta')->nullable();
            $table->integer('sem_verde_desde')->nullable();
            $table->integer('sem_verde_hasta')->nullable();

            $table->unique(['sku', 'company_id'], 'company_id_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
