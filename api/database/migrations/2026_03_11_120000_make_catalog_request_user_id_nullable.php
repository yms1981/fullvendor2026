<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * catalog_request.user_id es FK a users.id; permitir null cuando no hay vendedor asignado.
     * Alineado con: 2024_07_18 (create), 2024_07_29 (FK user_id->users.id), 2024_07_24 (unsignedBigInteger).
     * dropForeign(['user_id']) usa la convención Laravel {table}_{column}_foreign.
     */
    public function up(): void
    {
        Schema::table('catalog_request', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('catalog_request', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     * Nota: si ya existen filas con user_id NULL, el down() fallará al hacer nullable(false).
     */
    public function down(): void
    {
        Schema::table('catalog_request', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('catalog_request', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
