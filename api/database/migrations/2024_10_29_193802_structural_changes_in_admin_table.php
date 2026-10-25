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
        Schema::disableForeignKeyConstraints();
        Schema::table('admin', function (Blueprint $table) {
            //change the primaryKey column name
            if (Schema::hasColumn('admin', 'userId')) {
                $table->renameColumn('userId', 'id');
            }

            //add new column with the user relationship
            if (!Schema::hasColumn('admin', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }

            //drop columns
            if (Schema::hasColumn('admin', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('admin', 'username')) {
                $table->dropColumn('username');
            }
            if (Schema::hasColumn('admin', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('admin', 'password')) {
                $table->dropColumn('password');
            }
        });
        Schema::table('admin', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            if (Schema::hasColumn('admin', 'id')) {
                $table->renameColumn('id', 'userId');
            }

            if (Schema::hasColumn('admin', 'user_id')) {
                if (Schema::hasIndex('admin', 'admin_user_id_foreign')) {
                    $table->dropForeign(['user_id']);
                }
                $table->dropColumn('user_id');
            }

            if (!Schema::hasColumn('admin', 'name')) {
                $table->string('name',length: 255)->nullable(); //lengths that the table already has
            }
            if (!Schema::hasColumn('admin', 'username')) {
                $table->string('username',length: 500)->nullable();
            }
            if (!Schema::hasColumn('admin', 'email')) {
                $table->string('email',length: 500)->nullable();
            }
            if (!Schema::hasColumn('admin', 'password')) {
                $table->string('password',length: 1000)->nullable();
            }
        });
    }
};
