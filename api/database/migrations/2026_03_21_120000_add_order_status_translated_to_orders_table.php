<?php

use App\Models\Order;
use App\Models\StatusOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Texto traducido del estado (name_status_* según language_id de la orden), denormalizado desde status_orders.
     */
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'status_id')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'order_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('order_status', 255)->nullable()->after('status_id');
            });
        }

        Order::withoutEvents(function () {
            Order::query()
                ->whereNotNull('status_id')
                ->orderBy('order_id')
                ->chunkById(200, function ($orders) {
                    foreach ($orders as $order) {
                        $so = StatusOrder::query()->where('id_status', $order->status_id)->first();
                        $label = Order::translatedStatusOrderLabel($so, (int) ($order->language_id ?? 1));
                        DB::table('orders')->where('order_id', $order->order_id)->update(['order_status' => $label]);
                    }
                }, 'order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'order_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('order_status');
            });
        }
    }
};
