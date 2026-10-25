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
        if(Schema::hasTable('rol')) {
            Schema::table('rol', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });

            Schema::rename('rol', 'manager_profiles');
        }

        if(Schema::hasTable('user_profiles')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });

            Schema::rename('user_profiles', 'seller_profiles');
        }
        if(!Schema::hasIndex('manager_profiles','manager_profiles_company_id_foreign')) {
            Schema::table('manager_profiles', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });
        }
        Schema::disableForeignKeyConstraints();
        if(!Schema::hasIndex('seller_profiles','seller_profiles_company_id_foreign')) {
            Schema::table('seller_profiles', function (Blueprint $table) {
                $table->foreign('company_id')->references('company_id')->on('companies');
            });
        }
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manager_profiles', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::rename('manager_profiles', 'rol');
        Schema::rename('seller_profiles', 'user_profiles');

        Schema::table('rol', function (Blueprint $table) {
            $table->foreign('company_id')->references('company_id')->on('companies');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->foreign('company_id')->references('company_id')->on('companies');
        });
    }
};
