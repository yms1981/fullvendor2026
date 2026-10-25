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
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'currency')) {
                $table->dropForeign(['currency']);
            }
        });
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'currency_code_id')) {
                $table->dropForeign(['currency_code_id']);
            }
        });

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('currency');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
