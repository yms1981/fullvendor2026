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
        Schema::table('units', function (Blueprint $table) {
            if(Schema::hasColumn('units', 'default')){
                $table->dropColumn('default');
            }
            if(Schema::hasColumn('units', 'id_kor')){
                $table->dropColumn('id_kor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            if(!Schema::hasColumn('units', 'default')){
                $table->boolean('default')->default(false);
            }
            if(!Schema::hasColumn('units', 'id_kor')){
                $table->bigInteger('id_kor')->nullable();
            }
        });
    }
};
