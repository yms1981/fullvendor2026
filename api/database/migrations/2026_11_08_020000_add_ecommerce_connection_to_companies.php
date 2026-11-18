<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        if (! Schema::hasColumn('companies', 'ecommerce_enabled')) {
            $after = Schema::hasColumn('companies', 'erp_company_token')
                ? 'erp_company_token'
                : (Schema::hasColumn('companies', 'erp_url') ? 'erp_url' : 'zone');
            DB::statement("ALTER TABLE `companies` ADD COLUMN `ecommerce_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `{$after}`");
        }

        if (! Schema::hasColumn('companies', 'ecommerce_url')) {
            $after = Schema::hasColumn('companies', 'ecommerce_enabled') ? 'ecommerce_enabled' : 'erp_company_token';
            DB::statement("ALTER TABLE `companies` ADD COLUMN `ecommerce_url` VARCHAR(500) NULL DEFAULT NULL AFTER `{$after}`");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        foreach (['ecommerce_url', 'ecommerce_enabled'] as $column) {
            if (Schema::hasColumn('companies', $column)) {
                Schema::table('companies', function ($table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
