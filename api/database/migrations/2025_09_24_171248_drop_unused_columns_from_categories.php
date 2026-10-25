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
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'category_id')) {
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('categories', 'language_id')) {
                $table->dropForeign('categories_language_id_foreign');
                $table->dropColumn('language_id');
            }
            if (Schema::hasColumn('categories', 'id_kor')) {
                $table->dropColumn('id_kor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable();
            }
            if (!Schema::hasColumn('categories', 'language_id')) {
                $table->foreignId('language_id')->nullable()->constrained('languages','language_id')->onDelete('set null');
            }
            if (!Schema::hasColumn('categories', 'id_kor')) {
                $table->unsignedBigInteger('id_kor')->nullable();
            }
        });
    }
};
