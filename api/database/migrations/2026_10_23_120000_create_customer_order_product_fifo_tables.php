<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_order_product_fifo')) {
            Schema::create('customer_order_product_fifo', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('product_id');
                $table->string('sku', 128)->nullable();
                $table->unsignedBigInteger('source_order_id');
                // order_details.detail_id es bigint firmado en el dump.
                $table->bigInteger('source_detail_id');
                $table->string('source_order_number', 64)->nullable();
                $table->decimal('sale_price', 18, 4)->default(0);
                $table->decimal('qty_sold', 18, 4)->default(0);
                $table->decimal('qty_credited', 18, 4)->default(0);
                $table->decimal('qty_remaining', 18, 4)->default(0);
                $table->dateTime('opened_at');
                $table->string('status', 16)->default('open');
                $table->timestamps();

                $table->unique('source_detail_id', 'fifo_source_detail_unique');
                $table->index(['company_id', 'customer_id', 'product_id', 'opened_at'], 'fifo_company_customer_product_opened');
                $table->index(['company_id', 'product_id', 'qty_remaining', 'opened_at'], 'fifo_company_product_remaining');
                $table->index('source_order_id', 'fifo_source_order_id');
                $table->index('customer_id', 'fifo_customer_id');
                $table->index('product_id', 'fifo_product_id');

                $table->foreign('company_id', 'fifo_company_foreign')
                    ->references('company_id')->on('companies')->cascadeOnDelete();
                $table->foreign('customer_id', 'fifo_customer_foreign')
                    ->references('customer_id')->on('customers')->cascadeOnDelete();
                $table->foreign('product_id', 'fifo_product_foreign')
                    ->references('pro_id')->on('products')->cascadeOnDelete();
                $table->foreign('source_order_id', 'fifo_source_order_foreign')
                    ->references('order_id')->on('orders')->cascadeOnDelete();
                $table->foreign('source_detail_id', 'fifo_source_detail_foreign')
                    ->references('detail_id')->on('order_details')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('customer_order_product_fifo_allocations')) {
            Schema::create('customer_order_product_fifo_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fifo_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('credit_order_id');
                $table->bigInteger('credit_detail_id');
                $table->string('credit_order_number', 64)->nullable();
                $table->decimal('qty', 18, 4)->default(0);
                $table->decimal('credit_price', 18, 4)->default(0);
                $table->timestamps();

                $table->index('fifo_id', 'fifo_alloc_fifo_id');
                $table->index('credit_order_id', 'fifo_alloc_credit_order');
                $table->index('credit_detail_id', 'fifo_alloc_credit_detail');
                $table->index(['company_id', 'customer_id', 'product_id'], 'fifo_alloc_company_customer_product');

                $table->foreign('fifo_id', 'fifo_alloc_fifo_foreign')
                    ->references('id')->on('customer_order_product_fifo')->cascadeOnDelete();
                $table->foreign('company_id', 'fifo_alloc_company_foreign')
                    ->references('company_id')->on('companies')->cascadeOnDelete();
                $table->foreign('credit_order_id', 'fifo_alloc_credit_order_foreign')
                    ->references('order_id')->on('orders')->cascadeOnDelete();
                $table->foreign('credit_detail_id', 'fifo_alloc_credit_detail_foreign')
                    ->references('detail_id')->on('order_details')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_order_product_fifo_allocations');
        Schema::dropIfExists('customer_order_product_fifo');
    }
};
