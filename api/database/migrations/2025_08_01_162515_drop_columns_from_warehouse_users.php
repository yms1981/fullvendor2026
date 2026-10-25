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
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('warehouse_users', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->change();
            });

            Schema::table('warehouse_users', function (Blueprint $table) {
                $table->dropPrimary();

                $columns = [
                    'unique_id', 'first_name', 'last_name', 'username',
                    'email', 'password', 'phone_number', 'cell_number',
                    'fax', 'profile_image', 'email_verification', 'otp',
                    'status', 'token', 'add_customer', 'send_catalog',
                    'update_customer',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('warehouse_users', $column)) {
                        $table->dropColumn($column);
                    }
                }

                $table->unsignedBigInteger('warehouse_id')->nullable()->after('company_id');

                $table->primary(['user_id', 'company_id', 'warehouse_id']);

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_users', function (Blueprint $table) {
            $table->dropPrimary(['user_id', 'company_id', 'warehouse_id']);

            $table->dropForeign(['user_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');

            $table->unsignedBigInteger('user_id')->autoIncrement()->change();

            $table->primary('user_id');

            $table->string('unique_id', 255)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('username', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('phone_number', 255)->nullable();
            $table->string('cell_number', 255)->nullable();
            $table->string('fax', 255)->nullable();
            $table->string('profile_image', 255)->nullable();
            $table->tinyInteger('email_verification')->nullable()->default(0);
            $table->string('otp', 255)->nullable();
            $table->smallInteger('status')->nullable()->default(1);
            $table->text('token')->nullable();
            $table->integer('add_customer')->nullable()->default(0);
            $table->integer('send_catalog')->nullable()->default(0);
            $table->integer('update_customer')->nullable()->default(0);
        });
    }
};
