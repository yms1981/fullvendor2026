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
        Schema::create('deviceinfos', function (Blueprint $table) {
            $table->string('deviceinfo', 200)->primary();
            $table->string('so', 200);
            $table->string('version', 200);
            $table->date('created')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->string('versionname', 300)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deviceinfos');
    }
};
