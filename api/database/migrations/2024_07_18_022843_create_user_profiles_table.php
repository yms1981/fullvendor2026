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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->bigIncrements('profile_id');
            $table->string('profile_name')->nullable();
            $table->bigInteger('company_id')->nullable();
            $table->tinyInteger('order_discount')->default(0)->comment('0=No 1=Yes');
            $table->string('order_disc_price')->nullable();
            $table->tinyInteger('order_net_discount')->default(0)->comment('0=No 1=Yes');
            $table->string('order_net_disc_price')->nullable();
            $table->tinyInteger('can_change_price')->default(0)->comment('0=No 1=Yes');
            $table->tinyInteger('can_send_catalog')->default(0)->comment('0=No 1=Yes');
            $table->tinyInteger('can_create_customer')->default(0)->comment('0=No 1=Yes');
            $table->dateTime('created')->nullable();
            $table->smallInteger('status')->nullable()->default(1)->comment('0=InActive 1= Active
');
            $table->integer('default')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
