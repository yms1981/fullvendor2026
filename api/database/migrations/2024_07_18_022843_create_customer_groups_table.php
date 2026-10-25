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
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->bigInteger('group_id', true);
            $table->integer('language_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('percentage_on_price')->nullable();
            $table->double('percent_price_amount')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->tinyInteger('group_status')->default(1);
            $table->bigInteger('id_kor')->default(1);
            $table->integer('default')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
