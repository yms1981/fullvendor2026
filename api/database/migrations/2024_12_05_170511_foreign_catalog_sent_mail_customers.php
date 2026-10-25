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
        Schema::disableForeignKeyConstraints();
        Schema::table('catalog_sent_mail', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_sent_mail', 'customerIds')) {
                $table->unsignedBigInteger('customerIds')->change();
                $table->renameColumn('customerIds', 'customer_id');
                $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            }
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_sent_mail', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_sent_mail', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->renameColumn('customer_id','customerIds');
                $table->longText('customerIds')->nullable()->change();
            }
        });
    }
};
