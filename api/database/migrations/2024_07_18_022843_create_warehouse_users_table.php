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
        Schema::create('warehouse_users', function (Blueprint $table) {
            $table->bigIncrements('user_id');
            $table->string('unique_id')->nullable()->default('');
            $table->bigInteger('company_id')->nullable();
            $table->string('first_name')->nullable()->default('');
            $table->string('last_name')->nullable()->default('');
            $table->string('username')->nullable()->default('');
            $table->string('email')->nullable()->default('');
            $table->string('password')->nullable()->default('');
            $table->string('phone_number')->nullable()->default('');
            $table->string('cell_number')->nullable()->default('');
            $table->string('fax')->nullable()->default('');
            $table->string('profile_image')->nullable()->default('');
            $table->tinyInteger('email_verification')->nullable()->default(0)->comment('0=Not Verified 1= Verified
');
            $table->string('otp')->nullable()->default('');
            $table->dateTime('created')->nullable();
            $table->smallInteger('status')->nullable()->default(1)->comment('0=InActive 1= Active
');
            $table->text('token')->nullable();
            $table->integer('add_customer')->nullable()->default(0);
            $table->integer('update_customer')->nullable()->default(0);
            $table->integer('send_catalog')->nullable()->default(0);
            $table->dateTime('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_users');
    }
};
