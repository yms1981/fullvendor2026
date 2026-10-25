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
        Schema::create('vendors', function (Blueprint $table) {
            $table->bigIncrements('user_id');
            $table->string('unique_id')->nullable();
            $table->bigInteger('company_id')->nullable();
            $table->string('first_name')->nullable()->default('');
            $table->string('last_name')->nullable()->default('');
            $table->string('username')->nullable()->default('');
            $table->string('email')->nullable()->default('');
            $table->string('password')->nullable()->default('');
            $table->integer('profile')->nullable();
            $table->string('phone_number')->nullable()->default('');
            $table->string('cell_number')->nullable()->default('');
            $table->string('fax')->nullable()->default('');
            $table->string('profile_image')->nullable()->default('');
            $table->tinyInteger('email_verification')->nullable()->default(0)->comment('0=Not Verified 1= Verified
');
            $table->string('otp')->nullable()->default('');
            $table->dateTime('created')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->smallInteger('status')->nullable()->default(1)->comment('0=InActive 1= Active
');
            $table->bigInteger('id_kor')->nullable()->default(0);
            $table->integer('default')->nullable();
            $table->text('token')->nullable();
            $table->integer('add_customer');
            $table->integer('update_customer');
            $table->integer('send_catalog');
            $table->integer('all_customers')->nullable()->default(0);
            $table->integer('proforma')->default(0);
            $table->integer('email_on_salesman_order')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
