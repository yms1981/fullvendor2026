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
        if (Schema::hasColumn('orders', 'order_status')){
            Schema::table('status_orders', function (Blueprint $table) {
               $table->tinyIncrements('id_status')->change();
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedTinyInteger('status_id')->nullable();
            });

            DB::table('orders')->select('order_id','order_status')->get()->each(function ($order) {
                $status = DB::table('status_orders')->where('cod_status', $order->order_status)->first();

                DB::table('orders')->where('order_id', $order->order_id)->update([
                    'status_id' => $status->id_status ?: 1,
                ]);
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedTinyInteger('status_id')->nullable(false)->change();
                $table->foreign('status_id')->references('id_status')->on('status_orders');

                $table->dropColumn('order_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'status_id')){
                $table->dropForeign(['status_id']);
                $table->dropColumn('status_id');
                $table->integer('order_status')->default(0);
            }
        });

        Schema::table('status_orders', function (Blueprint $table) {
            $table->integer('id_status',true)->change();
        });
    }
};
