<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mobile_devices')) {
            Schema::create('mobile_devices', function (Blueprint $table) {
                $table->id();
                $table->string('device_uuid', 191)->unique();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('device_name')->nullable();
                $table->string('device_type', 32)->nullable();
                $table->string('os_version', 64)->nullable();
                $table->string('app_version', 64)->nullable();
                $table->string('model', 128)->nullable();
                $table->string('manufacturer', 128)->nullable();
                $table->string('push_token', 512)->nullable();
                $table->dateTime('last_used_at')->nullable();
                $table->dateTime('last_sync_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('company_id')->references('company_id')->on('companies')->nullOnDelete();
                $table->index('user_id');
                $table->index('company_id');
                $table->index('last_sync_at');
            });
        }

        if (! Schema::hasTable('mobile_sync_logs')) {
            Schema::create('mobile_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mobile_device_id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('sync_mode', 16);
                $table->integer('changes_count')->default(0);
                $table->integer('changes_products')->default(0);
                $table->integer('changes_customers')->default(0);
                $table->integer('changes_categories')->default(0);
                $table->integer('changes_orders')->default(0);
                $table->dateTime('since_at')->nullable();
                $table->string('status', 16)->default('started');
                $table->dateTime('synced_at');
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('mobile_device_id')->references('id')->on('mobile_devices')->cascadeOnDelete();
                $table->foreign('company_id')->references('company_id')->on('companies')->nullOnDelete();
                $table->index('mobile_device_id');
                $table->index(['company_id', 'synced_at']);
            });
        }

        if (! Schema::hasTable('mobile_change_log')) {
            Schema::create('mobile_change_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('entity_group', 32);
                $table->unsignedBigInteger('entity_id');
                $table->string('action', 16);
                $table->dateTime('changed_at');
                $table->timestamps();

                $table->foreign('company_id')->references('company_id')->on('companies')->cascadeOnDelete();
                $table->index(['company_id', 'changed_at']);
                $table->index(['company_id', 'entity_group', 'changed_at']);
                $table->index(['entity_group', 'entity_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sync_logs');
        Schema::dropIfExists('mobile_change_log');
        Schema::dropIfExists('mobile_devices');
    }
};
