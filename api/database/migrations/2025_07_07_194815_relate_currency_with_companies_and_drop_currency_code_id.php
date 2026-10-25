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
                try {
                    $table->dropForeign(['currency']);
                } catch (\Throwable) {
                    // FK legacy puede tener otro nombre o no existir.
                }

                $table->foreign('currency')->references('id')->on('currency');
            }

            if (Schema::hasColumn('companies', 'currency_code_id')) {
                $table->dropColumn('currency_code_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'currency')) {
                $table->dropForeign(['currency']);
            }

            if (!Schema::hasColumn('companies', 'currency_code_id')) {
                $table->unsignedBigInteger('currency_code_id')->nullable();
            }
        });
    }
};
