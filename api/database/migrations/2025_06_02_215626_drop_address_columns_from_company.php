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
        if (Schema::hasColumn('companies', 'address') ||
            Schema::hasColumn('companies', 'country') ||
            Schema::hasColumn('companies', 'state')   ||
            Schema::hasColumn('companies', 'city')    ||
            Schema::hasColumn('companies', 'zip_code')) {

            Schema::table('companies', function (Blueprint $table) {
                if (Schema::hasColumn('companies', 'address')) {
                    $table->dropColumn('address');
                }
                if (Schema::hasColumn('companies', 'country')) {
                    $table->dropColumn('country');
                }
                if (Schema::hasColumn('companies', 'state')) {
                    $table->dropColumn('state');
                }
                if (Schema::hasColumn('companies', 'city')) {
                    $table->dropColumn('city');
                }
                if (Schema::hasColumn('companies', 'zip_code')) {
                    $table->dropColumn('zip_code');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('companies', 'address') ||
            !Schema::hasColumn('companies', 'country') ||
            !Schema::hasColumn('companies', 'state')   ||
            !Schema::hasColumn('companies', 'city')    ||
            !Schema::hasColumn('companies', 'zip_code')) {

            Schema::table('companies', function (Blueprint $table) {
                if (!Schema::hasColumn('companies', 'address')) {
                    $table->text('address')->nullable();
                }
                if (!Schema::hasColumn('companies', 'country')) {
                    $table->string('country', 255)->nullable();
                }
                if (!Schema::hasColumn('companies', 'state')) {
                    $table->string('state', 255)->nullable();
                }
                if (!Schema::hasColumn('companies', 'city')) {
                    $table->string('city', 255)->nullable();
                }
                if (!Schema::hasColumn('companies', 'zip_code')) {
                    $table->string('zip_code', 255)->nullable();
                }
            });
        }
    }
};
