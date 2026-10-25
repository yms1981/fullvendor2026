<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat empresarial entre Sellers, Warehouse y Managers.
 * Fuente operativa de sync: dump SQL + schema:sync; esta migración documenta el cambio.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_conversations')) {
            Schema::create('company_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('subject')->nullable();
                $table->string('context_type', 50)->nullable();
                $table->unsignedBigInteger('context_id')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('company_id');
                $table->index(['company_id', 'last_message_at']);
                $table->index(['context_type', 'context_id']);
                $table->foreign('company_id')->references('company_id')->on('companies')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users');
            });
        }

        if (! Schema::hasTable('company_conversation_participants')) {
            Schema::create('company_conversation_participants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id');
                $table->enum('role_type', ['seller', 'warehouse', 'manager', 'customer']);
                $table->timestamp('last_read_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['conversation_id', 'user_id'], 'company_conversation_participants_unique');
                $table->foreign('conversation_id')->references('id')->on('company_conversations')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('company_conversation_messages')) {
            Schema::create('company_conversation_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id');
                $table->enum('message_type', ['text', 'image', 'audio'])->default('text');
                $table->longText('body')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['conversation_id', 'created_at'], 'company_conversation_messages_conversation_created_index');
                $table->foreign('conversation_id')->references('id')->on('company_conversations')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users');
            });
        } elseif (! Schema::hasColumn('company_conversation_messages', 'message_type')) {
            Schema::table('company_conversation_messages', function (Blueprint $table) {
                $table->enum('message_type', ['text', 'image', 'audio'])->default('text')->after('user_id');
            });
        }

        if (! Schema::hasTable('company_conversation_attachments')) {
            Schema::create('company_conversation_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id');
                $table->string('disk', 50)->default('minio');
                $table->string('path', 500);
                $table->string('mime_type', 120);
                $table->string('original_name')->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->decimal('duration_seconds', 10, 2)->nullable();
                $table->timestamps();

                $table->index('message_id');
                $table->foreign('message_id')->references('id')->on('company_conversation_messages')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_conversation_attachments');
        Schema::dropIfExists('company_conversation_messages');
        Schema::dropIfExists('company_conversation_participants');
        Schema::dropIfExists('company_conversations');
    }
};
