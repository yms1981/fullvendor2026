<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_scheduled_task_logs')) {
            return;
        }

        Schema::create('company_scheduled_task_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('scheduled_task_id')->nullable();
            $table->string('task_type', 64);
            $table->string('trigger', 32)->default('manual');
            $table->string('status', 32);
            $table->text('message')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at'], 'cstl_company_created_idx');
            $table->index(['company_id', 'task_type', 'created_at'], 'cstl_company_type_created_idx');
            $table->foreign('company_id', 'cstl_company_id_foreign')
                ->references('company_id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_scheduled_task_logs');
    }
};
