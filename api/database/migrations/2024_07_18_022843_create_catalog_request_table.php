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
        Schema::create('catalog_request', function (Blueprint $table) {
            $table->bigInteger('request_id', true);
            $table->integer('user_id')->comment('Pk of sellers');
            $table->integer('customer_id')->comment('PK of customers');
            $table->tinyText('descriptions')->nullable();
            $table->dateTime('created')->nullable()->useCurrent();
            $table->timestamp('updated')->useCurrentOnUpdate()->useCurrent();
            $table->tinyInteger('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_request');
    }
};
