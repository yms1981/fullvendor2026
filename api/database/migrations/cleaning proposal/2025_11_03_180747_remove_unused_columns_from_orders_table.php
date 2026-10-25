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
        Schema::table('orders', function (Blueprint $table) {
            $columns = [
                'payment_method',
                'payment_status',
                'transaction_id',
                'delivery_status',
                'approved_by',
                'warehouse_assign_date',
                'token_pass',
                'send_email_order',
                'date_rejected_email',
                'date_expired_email',
                'amount',
                'discount_a',
                'total_amount',
                'amount_delivered',
                'discount_delivered',
                'total_delivered',
                'portal_vendor',
                'virified_byportal',
                'id_kor',
                'update_bybackend',
                'discount_type_since_movil',
                'discount_movil',
                'leido',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('orders', 'language_id')) {
                $table->dropForeign(['language_id']);
                $table->dropColumn('language_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'language_id')) {
                $table->unsignedBigInteger('language_id')->nullable();
                $table->foreign('language_id')->references('language_id')->on('languages')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->tinyInteger('payment_method')->nullable();
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->tinyInteger('payment_status')->nullable();
            }
            if (!Schema::hasColumn('orders', 'transaction_id')) {
                $table->string('transaction_id', 255)->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_status')) {
                $table->integer('delivery_status')->nullable();
            }
            if (!Schema::hasColumn('orders', 'approved_by')) {
                $table->string('approved_by', 255)->nullable();
            }
            if (!Schema::hasColumn('orders', 'warehouse_assign_date')) {
                $table->dateTime('warehouse_assign_date')->nullable();
            }
            if (!Schema::hasColumn('orders', 'token_pass')) {
                $table->string('token_pass', 255)->nullable();
            }
            if (!Schema::hasColumn('orders', 'send_email_order')) {
                $table->dateTime('send_email_order')->nullable();
            }
            if (!Schema::hasColumn('orders', 'date_rejected_email')) {
                $table->dateTime('date_rejected_email')->nullable();
            }
            if (!Schema::hasColumn('orders', 'date_expired_email')) {
                $table->dateTime('date_expired_email')->nullable();
            }
            if (!Schema::hasColumn('orders', 'amount')) {
                $table->double('amount')->nullable();
            }
            if (!Schema::hasColumn('orders', 'discount_a')) {
                $table->double('discount_a')->nullable();
            }
            if (!Schema::hasColumn('orders', 'total_amount')) {
                $table->double('total_amount')->nullable();
            }
            if (!Schema::hasColumn('orders', 'amount_delivered')) {
                $table->double('amount_delivered')->nullable();
            }
            if (!Schema::hasColumn('orders', 'discount_delivered')) {
                $table->double('discount_delivered')->nullable();
            }
            if (!Schema::hasColumn('orders', 'total_delivered')) {
                $table->double('total_delivered')->nullable();
            }
            if (!Schema::hasColumn('orders', 'portal_vendor')) {
                $table->integer('portal_vendor')->nullable();
            }
            if (!Schema::hasColumn('orders', 'virified_byportal')) {
                $table->dateTime('virified_byportal')->nullable();
            }
            if (!Schema::hasColumn('orders', 'id_kor')) {
                $table->bigInteger('id_kor')->nullable();
            }
            if (!Schema::hasColumn('orders', 'update_bybackend')) {
                $table->integer('update_bybackend')->nullable();
            }
            if (!Schema::hasColumn('orders', 'discount_type_since_movil')) {
                $table->integer('discount_type_since_movil')->nullable();
            }
            if (!Schema::hasColumn('orders', 'discount_movil')) {
                $table->double('discount_movil')->nullable();
            }
            if (!Schema::hasColumn('orders', 'leido')) {
                $table->integer('leido')->nullable();
            }
        });
    }
};
