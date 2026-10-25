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
        Schema::create('session_cart', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('company_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->double('total_amount')->nullable()->default(0);
            $table->double('sub_total')->nullable()->default(0);
            $table->double('discount_total')->nullable()->default(0);
            $table->integer('qty_products')->nullable()->default(0);
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->timestamp('update_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->string('session_token', 50)->nullable();
            $table->integer('is_closed')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_cart');
    }
};
