<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos personales/dirección en `users`: first_name, last_name, address, city, state, zip_code.
     * Todos opcionales (los formularios de alta/edición pueden enviarlos parcialmente).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 100)->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 100)->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address', 255)->nullable()->after('fax');
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'state')) {
                $table->string('state', 100)->nullable()->after('city');
            }
            if (! Schema::hasColumn('users', 'zip_code')) {
                $table->string('zip_code', 30)->nullable()->after('state');
            }
        });
    }

    public function down(): void
    {
        $toDrop = [];
        foreach (['zip_code', 'state', 'city', 'address', 'last_name', 'first_name'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                $toDrop[] = $col;
            }
        }
        if ($toDrop === []) {
            return;
        }
        Schema::table('users', function (Blueprint $table) use ($toDrop) {
            $table->dropColumn($toDrop);
        });
    }
};
