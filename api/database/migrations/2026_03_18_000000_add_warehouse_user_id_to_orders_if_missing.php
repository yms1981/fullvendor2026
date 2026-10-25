<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }
        $added = false;
        Schema::table('orders', function (Blueprint $table) use (&$added) {
            if (! Schema::hasColumn('orders', 'warehouse_user_id')) {
                $table->unsignedBigInteger('warehouse_user_id')->nullable()->after('company_id');
                $added = true;
            }
        });
        if ($added && Schema::hasTable('users')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreign('warehouse_user_id')->references('id')->on('users')->nullOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'warehouse_user_id')) {
            return;
        }
        Schema::table('orders', function (Blueprint $table) {
            try {
                $table->dropForeign(['warehouse_user_id']);
            } catch (\Throwable $e) {
            }
            $table->dropColumn('warehouse_user_id');
        });
    }
};
