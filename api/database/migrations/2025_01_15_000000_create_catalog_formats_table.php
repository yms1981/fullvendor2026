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
        Schema::create('formats', function (Blueprint $table) {
            $table->bigInteger('format_id', true);
            $table->string('format_name', 255);
            $table->text('format_description')->nullable()->comment('Descripción del formato');
            $table->string('template', 10000)->nullable()->comment('Plantilla HTML del formato de impresión');
            $table->integer('order')->default(0)->comment('Orden de visualización');
            $table->integer('is_default')->default(0)->comment('1 si es el formato por defecto');
            $table->unsignedBigInteger('company_id')->nullable()->comment('ID de la empresa propietaria del formato');
            $table->string('format_type', 50)->nullable()->comment('Tipo de formato: catalog, order, invoice, etc.');
            $table->bigInteger('related_id')->nullable()->comment('ID del recurso relacionado (catalog_id, order_id, etc.) según format_type');
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key a companies
            $table->foreign('company_id')
                  ->references('company_id')
                  ->on('companies')
                  ->onDelete('cascade');
            
            // Índices
            $table->index('company_id');
            $table->index('format_type');
            $table->index('related_id');
            $table->index('is_default');
            $table->index(['format_type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formats');
    }
};

