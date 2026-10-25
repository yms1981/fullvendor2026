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
        Schema::create('terms_of_sales', function (Blueprint $table) {
            $table->bigInteger('term_id', true);
            $table->integer('language_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('name')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->tinyInteger('term_status')->default(1);
            $table->integer('default')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_of_sales');
    }
};
