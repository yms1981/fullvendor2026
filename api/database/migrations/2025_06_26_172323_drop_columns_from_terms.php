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
        Schema::table('terms_of_sales', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropForeign(['user_id']);

            $table->dropColumn(['language_id', 'user_id', 'default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terms_of_sales', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->constrained('languages','language_id');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->integer('default')->nullable();
        });
    }
};
