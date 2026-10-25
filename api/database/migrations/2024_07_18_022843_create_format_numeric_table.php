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
        Schema::create('format_numeric', function (Blueprint $table) {
            $table->integer('format_id', true);
            $table->string('numeric_format', 50)->nullable();
            $table->string('radixPoint', 11)->nullable();
            $table->string('groupSeparator', 11)->nullable();
            $table->integer('digits')->nullable();
            $table->integer('default')->nullable();
            $table->string('code_regional', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('format_numeric');
    }
};
