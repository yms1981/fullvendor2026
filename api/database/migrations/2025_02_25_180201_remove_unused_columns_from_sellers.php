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
        $this->checkForDrop('first_name');
        $this->checkForDrop('last_name');
        $this->checkForDrop('username');
        $this->checkForDrop('email');
        $this->checkForDrop('password');
        $this->checkForDrop('profile');
        $this->checkForDrop('profile_image');
        $this->checkForDrop('email_verification');
        $this->checkForDrop('id_kor');
        $this->checkForDrop('default');
        $this->checkForDrop('token');

        Schema::table('sellers', function (Blueprint $table) {
           $table->unsignedBigInteger('user_id')->nullable()->change();
           $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('sellers', 'first_name')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->string('first_name',255)->nullable();
            });
        }

        if (!Schema::hasColumn('sellers', 'last_name')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->string('last_name',255)->nullable();
            });
        }

        if (!Schema::hasColumn('sellers', 'username')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->string('username',255)->nullable();
            });
        }

        if (!Schema::hasColumn('sellers', 'email')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->string('email',255)->nullable();
            });
        }

        if (!Schema::hasColumn('sellers', 'password')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->string('password',255)->nullable();
            });
        }

        if (!Schema::hasColumn('sellers', 'profile')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->integer('profile')->nullable();
            });
        }

        if (!Schema::hasColumn('sellers', 'profile_image')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->string('profile_image',255)->nullable();
            });
        }

        if (!Schema::hasColumn('sellers', 'email_verification')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->tinyInteger('email_verification')->nullable()->default(0);
            });
        }

        if (!Schema::hasColumn('sellers', 'id_kor')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->bigInteger('id_kor')->nullable();
            });
        }

        if (!Schema::hasColumn('sellers', 'default')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->integer('default')->nullable();
            });
        }

        if (!Schema::hasColumn('sellers', 'token')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->text('token')->nullable();
            });
        }

        Schema::table('sellers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }

    /**
     * @param string $column
     * @return void
     */
    public function checkForDrop(string $column): void
    {
        if (Schema::hasColumn('sellers', $column)) {
            Schema::table('sellers', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
