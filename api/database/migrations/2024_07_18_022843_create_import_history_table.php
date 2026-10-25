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
        Schema::create('import_history', function (Blueprint $table) {
            $table->bigInteger('import_id', true);
            $table->integer('company_id');
            $table->dateTime('import_date')->nullable();
            $table->string('importedfile');
            $table->integer('records_imported');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_history');
    }
};
