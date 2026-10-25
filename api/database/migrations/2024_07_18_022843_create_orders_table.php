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
        Schema::create('orders', function (Blueprint $table) {
            $table->string('tipo_d', 1)->nullable()->default('D');
            $table->bigInteger('order_id', true);
            $table->string('order_number')->nullable()->default('');
            $table->bigInteger('company_id')->nullable();
            $table->bigInteger('user_id')->nullable()->comment('Pk of vendors');
            $table->tinyInteger('language_id')->comment('PK of languages');
            $table->bigInteger('customer_id')->comment('PK of customers');
            $table->tinyInteger('payment_method')->default(0)->comment('0-CashOn Delivery, 1-Payment Gateway');
            $table->tinyInteger('payment_status')->default(0)->comment('0-Due, 1-Paid, 2-Refund, 3-Settled');
            $table->string('transaction_id')->nullable()->default('');
            $table->text('order_comments')->nullable();
            $table->integer('order_status')->default(0)->comment('0-Placed, 1-Dispatched, 2-Delivered, 3-Return Request, 4-Returned, 5-Received, 6-Cancelled,7-Approved,
8-Warehouse Assigned,
9-Partially Completed,
10-Warehouse Completed,
11-Order Completed,
12-Rejected
');
            $table->integer('delivery_status')->nullable()->comment('1=Full Delivered, 0=Partial Delivered');
            $table->float('discount', 12)->default(0);
            $table->tinyInteger('discount_type')->default(1)->comment('1 = in %, 2 = in $ ');
            $table->dateTime('created')->nullable();
            $table->timestamp('updated')->useCurrentOnUpdate()->useCurrent();
            $table->string('approved_by')->nullable()->default('');
            $table->text('delivery_notes')->nullable();
            $table->integer('warehouse_user_id')->nullable()->comment('PK of warehouse_users');
            $table->dateTime('warehouse_assign_date')->nullable();
            $table->string('source', 50)->default('app');
            $table->text('internal_notes')->default('');
            $table->string('token_pass')->nullable()->default('');
            $table->dateTime('date_confirmed')->nullable();
            $table->dateTime('send_email_order')->nullable();
            $table->dateTime('date_rejected_email')->nullable();
            $table->dateTime('date_expired_email')->nullable();
            $table->integer('expiration_days')->nullable();
            $table->double('amount')->nullable()->default(0);
            $table->double('discount_a')->nullable()->default(0);
            $table->double('total_amount')->nullable()->default(0);
            $table->double('amount_delivered')->nullable()->default(0);
            $table->double('discount_delivered')->nullable()->default(0);
            $table->double('total_delivered')->nullable()->default(0);
            $table->integer('portal_vendor')->nullable();
            $table->dateTime('virified_byportal')->nullable();
            $table->bigInteger('id_kor')->nullable();
            $table->integer('cart_id')->nullable();
            $table->integer('catalog_id')->nullable();
            $table->integer('update_bybackend')->nullable()->default(0);
            $table->integer('discount_type_since_movil')->nullable();
            $table->decimal('discount_movil', 20)->nullable();
            $table->integer('leido')->nullable()->default(0);
            $table->integer('proforma')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
