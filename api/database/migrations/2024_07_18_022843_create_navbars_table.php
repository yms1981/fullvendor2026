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
        Schema::create('navbars', function (Blueprint $table) {
            $table->integer('navbar_id', true);
            $table->string('url_nav', 150)->nullable();
            $table->string('identify')->nullable();
            $table->string('name_eng', 150)->nullable();
            $table->string('name_spa')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent();
            $table->integer('father_id')->nullable();
            $table->string('icon', 150)->nullable();
            $table->integer('order')->nullable();
            $table->integer('config')->nullable();
            $table->integer('is_active_to_rol')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navbars');
    }
};
