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
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'default_customer_group_id')) {
                $table->foreignId('default_customer_group_id')->nullable()->constrained('customer_groups','group_id')->onDelete('set null');
            }
            if (!Schema::hasColumn('companies', 'default_seller_id')) {
                $table->foreignId('default_seller_id')->nullable()->constrained('sellers','id')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if(Schema::hasColumn('companies', 'default_customer_group_id')){
                $table->dropForeign('companies_default_customer_group_id');
                $table->dropColumn('default_customer_group_id');
            }
            if(Schema::hasColumn('companies', 'default_seller_id')){
                $table->dropForeign('companies_default_seller_id');
                $table->dropColumn('default_seller_id');
            }
        });
    }
};
