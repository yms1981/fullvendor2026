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
        Schema::create('status_order_flow', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\StatusOrder::class,'status_order_id');
            $table->foreignIdFor(\App\Models\OrderFlow::class,'order_flow_id');
            $table->primary(['status_order_id','order_flow_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_order_flow');
    }
};
