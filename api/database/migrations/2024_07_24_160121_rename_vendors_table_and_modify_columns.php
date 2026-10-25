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
        Schema::rename('vendors', 'sellers');

        Schema::table('sellers', function (Blueprint $table) {
            $table->renameColumn('user_id', 'id');
            $table->bigInteger('id')->unsigned()->autoIncrement()->change();
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
