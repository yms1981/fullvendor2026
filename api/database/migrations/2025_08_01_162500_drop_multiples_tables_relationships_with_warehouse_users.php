<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'warehouse_user_id')) {
                    $table->dropForeign(['warehouse_user_id']);
                    $table->dropColumn('warehouse_user_id');
                }

                $table->unsignedBigInteger('warehouse_id')->nullable()->after('company_id');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            });

            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'warehouse_user_id')) {
                    $table->dropForeign(['warehouse_user_id']);
                    $table->dropColumn('warehouse_user_id');
                }

                $table->unsignedBigInteger('warehouse_id')->nullable()->after('company_id');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            });

            Schema::table('wordersList', function (Blueprint $table) {
                if (Schema::hasColumn('wordersList', 'warehouse_user_id')) {
                    $table->dropForeign(['warehouse_user_id']);
                    $table->dropColumn('warehouse_user_id');
                }

                $table->unsignedBigInteger('warehouse_id')->nullable()->after('company_id');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            });


        });
        DB::statement('DROP VIEW IF EXISTS inventory_orders');
        DB::statement('DROP VIEW IF EXISTS myorders');
        DB::statement('DROP VIEW IF EXISTS productsListadoActivo');
        DB::statement('DROP VIEW IF EXISTS warehouseorders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('wordersList', function (Blueprint $table) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
                $table->unsignedBigInteger('warehouse_user_id')->nullable()->after('company_id');
                $table->foreign('warehouse_user_id')->references('user_id')->on('warehouse_users');
            });

            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
                $table->unsignedBigInteger('warehouse_user_id')->nullable()->after('company_id');
                $table->foreign('warehouse_user_id')->references('user_id')->on('warehouse_users');
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
                $table->unsignedBigInteger('warehouse_user_id')->nullable()->after('company_id');
                $table->foreign('warehouse_user_id')->references('user_id')->on('warehouse_users');
            });
        });
    }
};
