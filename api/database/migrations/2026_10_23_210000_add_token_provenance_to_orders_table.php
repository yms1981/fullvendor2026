<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Procedencia del alta de la orden (token Sanctum / usuario / rol).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'token_create')) {
                $table->string('token_create', 512)->nullable()->after('source')
                    ->comment('Identificador/hash del token con el que se creó la orden');
            }
            if (! Schema::hasColumn('orders', 'user_id_token')) {
                $table->unsignedBigInteger('user_id_token')->nullable()->after('token_create')
                    ->comment('users.id del token que creó la orden');
                $table->index('user_id_token', 'orders_user_id_token_index');
            }
            if (! Schema::hasColumn('orders', 'rol_token')) {
                $table->string('rol_token', 64)->nullable()->after('user_id_token')
                    ->comment('Rol del usuario en la compañía al crear (warehouse, seller, manager, …)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_id_token')) {
                try {
                    $table->dropIndex('orders_user_id_token_index');
                } catch (\Throwable) {
                }
            }
            $cols = array_values(array_filter([
                Schema::hasColumn('orders', 'rol_token') ? 'rol_token' : null,
                Schema::hasColumn('orders', 'user_id_token') ? 'user_id_token' : null,
                Schema::hasColumn('orders', 'token_create') ? 'token_create' : null,
            ]));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
