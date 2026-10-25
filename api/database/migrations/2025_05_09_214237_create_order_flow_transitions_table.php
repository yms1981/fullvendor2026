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
        Schema::create('order_flow_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(\App\Models\OrderFlow::class,'order_flow_id');
            $table->foreignIdFor(\App\Models\StatusOrder::class,'origin_status_order_id');;
            $table->foreignIdFor(\App\Models\StatusOrder::class,'destination_status_order_id');;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_flow_transitions');
    }
};
