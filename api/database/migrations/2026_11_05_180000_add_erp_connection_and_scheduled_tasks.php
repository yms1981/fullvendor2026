<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('companies')) {
            if (! Schema::hasColumn('companies', 'erp_enabled')) {
                DB::statement("ALTER TABLE `companies` ADD COLUMN `erp_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `zone`");
            }
            if (! Schema::hasColumn('companies', 'erp_company_code')) {
                DB::statement("ALTER TABLE `companies` ADD COLUMN `erp_company_code` VARCHAR(100) NULL DEFAULT NULL AFTER `erp_enabled`");
            }
            if (! Schema::hasColumn('companies', 'erp_url')) {
                $after = Schema::hasColumn('companies', 'erp_company_code') ? 'erp_company_code' : 'erp_enabled';
                DB::statement("ALTER TABLE `companies` ADD COLUMN `erp_url` VARCHAR(500) NULL DEFAULT NULL AFTER `{$after}`");
            }
            if (! Schema::hasColumn('companies', 'erp_company_token')) {
                DB::statement("ALTER TABLE `companies` ADD COLUMN `erp_company_token` TEXT NULL DEFAULT NULL AFTER `erp_url`");
            }
        }

        if (! Schema::hasTable('company_scheduled_tasks')) {
            Schema::create('company_scheduled_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('task_type', 64);
                $table->boolean('enabled')->default(false);
                $table->string('frequency', 32)->default('manual');
                $table->timestamp('last_run_at')->nullable();
                $table->string('last_status', 32)->nullable();
                $table->text('last_message')->nullable();
                $table->timestamp('next_run_at')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'task_type'], 'company_scheduled_tasks_company_type_unique');
                $table->index(['enabled', 'next_run_at'], 'company_scheduled_tasks_due_index');
                $table->foreign('company_id', 'company_scheduled_tasks_company_id_foreign')
                    ->references('company_id')
                    ->on('companies')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_scheduled_tasks');

        if (Schema::hasTable('companies')) {
            foreach (['erp_company_token', 'erp_url', 'erp_company_code', 'erp_enabled'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    Schema::table('companies', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }
    }
};
