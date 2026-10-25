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
        Schema::create('order_details', function (Blueprint $table) {
            $table->bigInteger('detail_id', true);
            $table->bigInteger('order_id')->comment('PK of orders');
            $table->bigInteger('product_id')->comment('PK of products');
            $table->double('qty')->default(0);
            $table->double('delivered_quantity');
            $table->float('discount');
            $table->tinyInteger('discount_type')->default(1)->comment('1 = in %, 2 = in $');
            $table->double('sale_price')->default(0);
            $table->double('fob_price')->default(0);
            $table->double('sale_price_app')->default(0);
            $table->double('purchase_price')->default(0);
            $table->text('comment');
            $table->timestamp('created')->useCurrentOnUpdate()->useCurrent();
            $table->bigInteger('id_kor');
            $table->text('comments');
            $table->timestamp('modify_date')->nullable();
            $table->timestamp('fixed_pricedate')->nullable();
            $table->double('amount_sales')->nullable()->default(0);
            $table->double('discount_amount')->nullable()->default(0);
            $table->double('total_amount')->nullable()->default(0);
            $table->double('amount_delivered')->nullable()->default(0);
            $table->double('discount_delivered')->nullable()->default(0);
            $table->double('total_delivered')->nullable()->default(0);
            $table->double('amount')->nullable()->default(0);
            $table->double('discountt')->nullable()->default(0);
            $table->timestamp('delivered_date')->nullable();
            $table->double('total')->nullable()->default(0);
            $table->string('groupcustomer', 200)->nullable();
            $table->string('tipolista', 200)->nullable();
            $table->double('perc_price')->nullable();
            $table->double('salesp')->nullable();
            $table->double('impprice')->nullable();
            $table->double('totalprice')->nullable();
            $table->double('import_price')->nullable()->default(0);
            $table->double('pack')->nullable()->default(0);
            $table->double('delivered_pack')->nullable();
            $table->integer('company_id')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
