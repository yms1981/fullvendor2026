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
        Schema::create('catalog', function (Blueprint $table) {
            $table->bigInteger('catalog_id', true);
            $table->string('catalog_name');
            $table->integer('company_id');
            $table->text('cat_id')->comment('pk of categories');
            $table->text('product_name')->nullable();
            $table->integer('status');
            $table->dateTime('created')->nullable();
            $table->longText('customer_emails')->nullable();
            $table->dateTime('email_sent_on')->nullable();
            $table->string('attach', 255)->nullable();
            $table->integer('dayexpired')->default(0);
            $table->integer('default_catalog')->nullable();
            $table->string('cabeza_pagina')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog');
    }
};
