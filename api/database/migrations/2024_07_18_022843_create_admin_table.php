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
        Schema::create('admin', function (Blueprint $table) {
            $table->bigInteger('userId', true);
            $table->string('name')->nullable();
            $table->string('username', 500)->nullable();
            $table->string('email', 500)->nullable();
            $table->string('password', 1000)->nullable();
            $table->timestamp('created')->nullable();
            $table->timestamp('edited')->useCurrentOnUpdate()->useCurrent();
            $table->smallInteger('status')->nullable()->default(1)->comment('1(Active), 0(Deactive)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};
