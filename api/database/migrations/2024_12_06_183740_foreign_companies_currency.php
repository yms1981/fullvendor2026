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
        Schema::disableForeignKeyConstraints();
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'currency')) {

                $table->unsignedBigInteger('currency')->nullable()->change();
                DB::table('companies')->where('currency', '')->update(['currency' => null]);

                $table->foreign('currency')->references('currency_id')->on('currency');

            }
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'currency')) {
                $table->dropForeign(['currency']);
                $table->string('currency')->nullable()->change();
            }
        });
    }
};
