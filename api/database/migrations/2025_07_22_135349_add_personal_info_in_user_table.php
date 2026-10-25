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
        Schema::table('users', function (Blueprint $table) {
            $table->string('image')->nullable()->after('password');
            $table->string('phone', 30)->nullable()->after('image');
            $table->string('cellphone', 30)->nullable()->after('phone');
            $table->string('fax', 30)->nullable()->after('cellphone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['image', 'phone', 'cellphone', 'fax']);
        });
    }
};
