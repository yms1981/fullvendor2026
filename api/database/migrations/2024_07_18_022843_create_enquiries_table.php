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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->bigInteger('enquiryId', true);
            $table->string('name', 500)->nullable();
            $table->string('email', 500)->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created')->nullable()->useCurrent();
            $table->smallInteger('status')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
