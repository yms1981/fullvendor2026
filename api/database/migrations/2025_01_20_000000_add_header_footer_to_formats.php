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
        Schema::table('formats', function (Blueprint $table) {
            if (!Schema::hasColumn('formats', 'format_header')) {
                $table->text('format_header')->nullable()->comment('Encabezado del formato de impresión')->after('format_description');
            }
            if (!Schema::hasColumn('formats', 'format_footer')) {
                $table->text('format_footer')->nullable()->comment('Pie de página del formato de impresión')->after('format_header');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formats', function (Blueprint $table) {
            if (Schema::hasColumn('formats', 'format_header')) {
                $table->dropColumn('format_header');
            }
            if (Schema::hasColumn('formats', 'format_footer')) {
                $table->dropColumn('format_footer');
            }
        });
    }
};

