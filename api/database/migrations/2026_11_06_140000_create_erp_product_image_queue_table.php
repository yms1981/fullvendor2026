<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('erp_product_image_queue')) {
            return;
        }

        Schema::create('erp_product_image_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');
            $table->string('sku', 120);
            $table->string('image_url', 2048);
            $table->string('url_hash', 64);
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->boolean('is_default')->default(false);
            $table->string('status', 32)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'product_id', 'url_hash'], 'epi_queue_company_product_url_uq');
            $table->index(['status', 'id'], 'epi_queue_status_id_idx');
            $table->index(['company_id', 'status', 'id'], 'epi_queue_company_status_idx');
            $table->foreign('company_id', 'epi_queue_company_fk')
                ->references('company_id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_product_image_queue');
    }
};
