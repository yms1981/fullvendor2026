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
        Schema::create('inventory', function (Blueprint $table) {
            $table->bigInteger('company_id');
            $table->bigInteger('product_id');
            $table->bigInteger('kor_id')->nullable()->default(0);
            $table->float('qty')->nullable()->default(0);
            $table->float('sale_price')->nullable()->default(0);
            $table->float('purchased_price')->nullable()->default(0);
            $table->dateTime('modify_date')->nullable()->useCurrent();

            $table->primary(['company_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
