<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUnusedColumnsFromSeller extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (Schema::hasColumn('sellers', 'unique_id')) {
                $table->dropColumn('unique_id');
            }
            if (Schema::hasColumn('sellers', 'otp')) {
                $table->dropColumn('otp');
            }
            if (Schema::hasColumn('sellers', 'add_customer')) {
                $table->dropColumn('add_customer');
            }
            if (Schema::hasColumn('sellers', 'update_customer')) {
                $table->dropColumn('update_customer');
            }
            if (Schema::hasColumn('sellers', 'send_catalog')) {
                $table->dropColumn('send_catalog');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (!Schema::hasColumn('sellers', 'unique_id')) {
                $table->string('unique_id')->nullable();
            }
            if (!Schema::hasColumn('sellers', 'otp')) {
                $table->string('otp')->nullable();
            }
            if (!Schema::hasColumn('sellers', 'add_customer')) {
                $table->integer('add_customer');
            }
            if (!Schema::hasColumn('sellers', 'update_customer')) {
                $table->integer('update_customer');
            }
            if (!Schema::hasColumn('sellers', 'send_catalog')) {
                $table->integer('send_catalog');
            }
        });
    }
}
