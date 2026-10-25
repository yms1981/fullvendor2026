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
        #Schema::disableForeignKeyConstraints();
        Schema::drop('detail_car');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_cart_id_foreign');
            $table->dropColumn('cart_id');
        });
        Schema::drop('catalog_cart');
        #Schema::enableForeignKeyConstraints();
        DB::statement("DROP VIEW IF EXISTS `monto_cart`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //TODO: recreate detail_car with the modifications
        //TODO: recreate catalog_cart with the modifications
    }
};
