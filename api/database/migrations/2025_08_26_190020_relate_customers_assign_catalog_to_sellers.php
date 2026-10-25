<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RelateCustomersAssignCatalogToSellers extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'assign_catalog')) {
                $table->renameColumn('assign_catalog', 'seller_assign_catalog');
            }
        });

        // Limpieza: valores inválidos a NULL
        DB::table('customers')
            ->whereNotIn('seller_assign_catalog', function ($query) {
                $query->select('id')->from('sellers');
            })
            ->update(['seller_assign_catalog' => null]);

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'seller_assign_catalog')) {
                $table->unsignedBigInteger('seller_assign_catalog')->nullable()->change();
                $table->foreign('seller_assign_catalog')
                    ->references('id')->on('sellers')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'seller_assign_catalog')) {
                $table->dropForeign(['seller_assign_catalog']);
                $table->renameColumn('seller_assign_catalog', 'assign_catalog');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'assign_catalog')) {
                $table->integer('assign_catalog')->nullable()->default(0)->change();
            }
        });
    }
}
