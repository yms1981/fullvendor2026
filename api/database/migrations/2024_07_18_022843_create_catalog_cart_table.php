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
        Schema::create('catalog_cart', function (Blueprint $table) {
            $table->bigInteger('cart_id', true);
            $table->bigInteger('customer_id')->nullable()->comment('Pk of customers');
            $table->bigInteger('product_id')->nullable()->comment('PK of products');
            $table->integer('qty')->nullable();
            $table->dateTime('created')->nullable();
            $table->timestamp('updated')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->string('token_car')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('catalog_id')->nullable();
            $table->dateTime('expired_date')->nullable();
            $table->string('token_kor')->nullable();
            $table->integer('section_address')->nullable();
            $table->integer('show_logofullvendor')->nullable();
            $table->integer('return_admin')->nullable();
            $table->integer('order_sent')->nullable();
            $table->string('catalog_text')->nullable();
            $table->integer('view')->nullable()->default(0);
            $table->integer('status_cart')->nullable();
            $table->integer('login_seller')->nullable();
            $table->integer('disable_total')->nullable();
            $table->string('type_cart', 50)->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->integer('father_id')->nullable();
            $table->string('cart_number', 50)->nullable();
            $table->integer('order_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_cart');
    }
};
