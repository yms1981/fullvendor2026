<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contadores diarios de envíos email / SMS / WhatsApp por compañía (Telecom).
 * Fuente operativa: dump SQL + schema:sync; esta migración documenta el cambio.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_messaging_daily')) {
            return;
        }

        Schema::create('company_messaging_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->date('stat_date');
            $table->unsignedInteger('email_count')->default(0);
            $table->unsignedInteger('sms_count')->default(0);
            $table->unsignedInteger('whatsapp_count')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'stat_date'], 'company_messaging_daily_company_date_unique');
            $table->index('stat_date', 'company_messaging_daily_stat_date_index');
            $table->foreign('company_id', 'company_messaging_daily_company_id_foreign')
                ->references('company_id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_messaging_daily');
    }
};
