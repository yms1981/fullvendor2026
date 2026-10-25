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
        Schema::create('catalog_sent_mail', function (Blueprint $table) {
            $table->bigInteger('catalog_sent_id', true);
            $table->integer('company_id');
            $table->integer('catlog_reqest_id')->comment('PK of catalog_request');
            $table->integer('hide_price')->comment('0=No,1=Yes');
            $table->integer('catalog_id')->comment('PK of catalog');
            $table->string('catalog_text', 1000)->nullable();
            $table->string('catalog_send_type')->nullable();
            $table->text('catalog_email_text')->nullable();
            $table->dateTime('send_date')->nullable();
            $table->longText('customer_emails')->nullable();
            $table->longText('customerIds')->nullable();
            $table->dateTime('expiration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_sent_mail');
    }
};
