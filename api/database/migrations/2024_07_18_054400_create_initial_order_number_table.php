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
        Schema::create('initial_order_number', function (Blueprint $table) {
            //$table->id('company_id');
            $table->foreignIdFor(\App\Models\Company::class, 'company_id')
                ->constrained("companies", 'company_id');
            $table->integer('initial_number');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_order_number');
    }
};
