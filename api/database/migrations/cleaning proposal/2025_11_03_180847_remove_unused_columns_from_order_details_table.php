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
        Schema::table('order_details', function (Blueprint $table) {
            $columnsToDrop = [
                'delivered_quantity',
                'discount_type',
                'sale_price_app',
                'purchase_price',
                'comment',
                'id_kor',
                'modify_date',
                'fixed_pricedate',
                'amount_sales',
                'discount_amount',
                'total_amount',
                'amount_delivered',
                'discount_delivered',
                'total_delivered',
                'amount',
                'discountt',
                'delivered_date',
                'total',
                'groupcustomer',
                'tipolista',
                'perc_price',
                'salesp',
                'impprice',
                'totalprice',
                'import_price',
                'pack',
                'delivered_pack',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('order_details', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('orders', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }

            if (Schema::hasColumn('order_details', 'comments')) {
                $table->text('comments')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'delivered_quantity')) {
                $table->double('delivered_quantity')->default(0);
            }
            if (!Schema::hasColumn('order_details', 'discount_type')) {
                $table->tinyInteger('discount_type')->default(1);
            }
            if (!Schema::hasColumn('order_details', 'sale_price_app')) {
                $table->double('sale_price_app')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'purchase_price')) {
                $table->double('purchase_price')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'comment')) {
                $table->text('comment')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'id_kor')) {
                $table->bigInteger('id_kor')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'modify_date')) {
                $table->timestamp('modify_date')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'fixed_pricedate')) {
                $table->timestamp('fixed_pricedate')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'amount_sales')) {
                $table->double('amount_sales')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'discount_amount')) {
                $table->double('discount_amount')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'total_amount')) {
                $table->double('total_amount')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'amount_delivered')) {
                $table->double('amount_delivered')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'discount_delivered')) {
                $table->double('discount_delivered')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'total_delivered')) {
                $table->double('total_delivered')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'amount')) {
                $table->double('amount')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'discountt')) {
                $table->double('discountt')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'delivered_date')) {
                $table->timestamp('delivered_date')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'total')) {
                $table->double('total')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'groupcustomer')) {
                $table->string('groupcustomer', 255)->nullable();
            }
            if (!Schema::hasColumn('order_details', 'tipolista')) {
                $table->string('tipolista', 255)->nullable();
            }
            if (!Schema::hasColumn('order_details', 'perc_price')) {
                $table->double('perc_price')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'salesp')) {
                $table->double('salesp')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'impprice')) {
                $table->double('impprice')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'totalprice')) {
                $table->double('totalprice')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'import_price')) {
                $table->double('import_price')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'pack')) {
                $table->double('pack')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'delivered_pack')) {
                $table->double('delivered_pack')->nullable();
            }
            if (!Schema::hasColumn('order_details', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
                $table->foreign('company_id')->references('company_id')->on('companies');
            }

            if (Schema::hasColumn('order_details', 'comments')) {
                $table->text('comments')->nullable(false)->change();
            }
        });
    }
};
