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
        Schema::create('customer_seller', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Customer::class,'customer_id')
                ->references('customer_id')->on('customers')
                ->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Vendor::class,'seller_id')
                ->references('id')->on('sellers')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_seller');
    }
};
