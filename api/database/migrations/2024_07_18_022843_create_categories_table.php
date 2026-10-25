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
        Schema::create('categories', function (Blueprint $table) {
            $table->bigInteger('cat_id', true);
            $table->bigInteger('category_id')->nullable();
            $table->integer('language_id')->nullable()->default(1);
            $table->integer('company_id')->default(0)->comment('PK of companies');
            $table->string('category_name')->nullable();
            $table->string('images')->nullable();
            $table->integer('category_status')->default(1);
            $table->dateTime('category_created_at')->nullable();
            $table->bigInteger('id_kor')->nullable()->default(0);
            $table->integer('category_default')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
