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
            if (!Schema::hasColumn('users', 'language_id')) {
                $table->unsignedBigInteger('language_id')->default(1)->after('email_verified_at');
                $table->foreign('language_id')->references('language_id')->on('languages');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'language_id')) {
                $table->dropConstrainedForeignId('language_id');
            }
        });
    }
};
