<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpstracker', function (Blueprint $table) {
            $table->index(['company_id', 'user_id', 'created'], 'idx_gpstracker_company_user_created');
        });
    }

    public function down(): void
    {
        Schema::table('gpstracker', function (Blueprint $table) {
            $table->dropIndex('idx_gpstracker_company_user_created');
        });
    }
};
