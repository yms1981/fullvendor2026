<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('images');

            $table->uuid('image')->nullable()->after('category_name');

            $table->foreign('image')
                ->references('uuid')
                ->on('images')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['image']);
            $table->dropColumn('image');

            $table->string('images');
        });
    }
};
