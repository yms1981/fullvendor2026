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
        Schema::table('products', function (Blueprint $table) {
            if(Schema::hasColumn('products', 'unit_type')){
                $table->dropColumn('unit_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if(!Schema::hasColumn('products', 'unit_type')){
                $table->string('unit_type',50)->nullable();
            }
        });
    }
};
