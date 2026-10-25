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
        Schema::create('detail_car', function (Blueprint $table) {
            $table->integer('detail_id', true);
            $table->integer('product_id')->nullable();
            $table->string('token_car')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('qty')->nullable();
            $table->dateTime('created')->nullable();
            $table->double('sale_price')->nullable()->default(0);
            $table->string('comment')->nullable();
            $table->integer('cart_id')->nullable();
            $table->integer('session_id')->nullable();
            $table->integer('cookie_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_car');
    }
};
