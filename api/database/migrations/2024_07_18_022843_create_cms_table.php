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
        Schema::create('cms', function (Blueprint $table) {
            $table->bigInteger('pageId', true);
            $table->string('pageTitle', 100)->nullable();
            $table->tinyText('pageText')->nullable();
            $table->string('link')->nullable();
            $table->longText('content')->nullable();
            $table->timestamp('created')->nullable();
            $table->timestamp('modified')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms');
    }
};
