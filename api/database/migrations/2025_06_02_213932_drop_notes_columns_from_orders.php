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
        if (Schema::hasColumn('orders', 'order_comments') ||
            Schema::hasColumn('orders', 'delivery_notes') ||
            Schema::hasColumn('orders', 'internal_notes') ||
            Schema::hasColumn('orders', 'new_address_json')) {

            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'order_comments')) {
                    $table->dropColumn('order_comments');
                }
                if (Schema::hasColumn('orders', 'delivery_notes')) {
                    $table->dropColumn('delivery_notes');
                }
                if (Schema::hasColumn('orders', 'internal_notes')) {
                    $table->dropColumn('internal_notes');
                }
                if (Schema::hasColumn('orders', 'new_address_json')) {
                    $table->dropColumn('new_address_json');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('orders', 'order_comments') ||
            !Schema::hasColumn('orders', 'delivery_notes') ||
            !Schema::hasColumn('orders', 'internal_notes') ||
            !Schema::hasColumn('orders', 'new_address_json')) {

            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'order_comments')) {
                    $table->text('order_comments')->nullable();
                }
                if (!Schema::hasColumn('orders', 'delivery_notes')) {
                    $table->text('delivery_notes')->nullable();
                }

                if (!Schema::hasColumn('orders', 'internal_notes')) {
                    $table->text('internal_notes')->nullable();
                }

                if (!Schema::hasColumn('orders', 'new_address_json')) {
                    $table->string('new_address_json', 500)->nullable();
                }
            });
        }
    }
};
