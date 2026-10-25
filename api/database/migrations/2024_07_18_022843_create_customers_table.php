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
        Schema::create('customers', function (Blueprint $table) {
            $table->bigInteger('customer_id', true);
            $table->integer('language_id')->nullable()->default(1);
            $table->integer('company_id');
            $table->string('user_id')->nullable()->default(' ');
            $table->string('name')->nullable()->default(' ');
            $table->string('business_name')->nullable()->default(' ');
            $table->string('tax_id')->nullable()->default(' ');
            $table->integer('term_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->string('email')->nullable()->default(' ');
            $table->string('phone')->nullable()->default(' ');
            $table->string('cell_phone')->nullable()->default(' ');
            $table->string('notes', 50)->nullable()->default(' ');
            $table->string('commercial_address')->nullable()->default(' ');
            $table->string('commercial_delivery_address')->nullable()->default(' ');
            $table->string('commercial_country')->nullable()->default(' ');
            $table->string('commercial_state')->nullable()->default(' ');
            $table->string('commercial_city')->nullable()->default(' ');
            $table->string('commercial_zone')->nullable()->default(' ');
            $table->string('commercial_zip_code')->nullable()->default(' ');
            $table->string('dispatch_address')->nullable()->default(' ');
            $table->string('dispatch_delivery_address')->nullable()->default(' ');
            $table->string('dispatch_country')->nullable()->default(' ');
            $table->string('dispatch_state')->nullable()->default(' ');
            $table->string('dispatch_city')->nullable()->default(' ');
            $table->string('dispatch_zone')->nullable()->default(' ');
            $table->string('dispatch_zip_code')->nullable()->default(' ');
            $table->string('dispatch_shipping_notes', 50)->nullable()->default(' ');
            $table->tinyInteger('catalog_emails')->nullable()->default(0);
            $table->dateTime('customer_created_at')->nullable()->useCurrent();
            $table->tinyInteger('customer_status')->default(1);
            $table->string('cust_id_kor', 50)->nullable()->default('')->comment('Code');
            $table->bigInteger('id_kor')->nullable()->default(0);
            $table->integer('assign_catalog')->nullable()->default(0);
            $table->float('discount')->nullable()->default(0);
            $table->timestamp('modified_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->string('password', 250)->nullable();
            $table->double('balance')->nullable()->default(0);
            $table->double('sales')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
