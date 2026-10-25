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
        Schema::create('delivered_orders', function (Blueprint $table) {
            $table->bigInteger('delivered_id', true);
            $table->bigInteger('order_id')->comment('PK of orders');
            $table->bigInteger('product_id')->comment('PK of products');
            $table->float('delivered_quantity')->nullable();
            $table->timestamp('delivered_date')->useCurrentOnUpdate()->useCurrent();
            $table->double('delivered_pack')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivered_orders');
    }
};
