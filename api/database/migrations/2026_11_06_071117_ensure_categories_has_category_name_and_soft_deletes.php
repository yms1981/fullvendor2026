<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Garantiza el esquema canónico de categories (FullVendor V2 / ERP sync):
 * category_name + soft deletes. Algunas BDs legacy usaron `name` o perdieron la columna.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'category_name')) {
                $table->string('category_name')->nullable()->after('company_id');
            }

            if (! Schema::hasColumn('categories', 'category_status')) {
                $table->integer('category_status')->default(1);
            }

            if (! Schema::hasColumn('categories', 'category_default')) {
                $table->integer('category_default')->nullable();
            }

            if (! Schema::hasColumn('categories', 'category_created_at')) {
                $table->dateTime('category_created_at')->nullable();
            }

            if (! Schema::hasColumn('categories', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }

            if (! Schema::hasColumn('categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Si existe `name` (esquema tipo ERP) y category_name vacío → backfill.
        if (Schema::hasColumn('categories', 'name') && Schema::hasColumn('categories', 'category_name')) {
            DB::table('categories')
                ->where(function ($q) {
                    $q->whereNull('category_name')->orWhere('category_name', '');
                })
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->update([
                    'category_name' => DB::raw('name'),
                ]);
        }
    }

    public function down(): void
    {
        // No se revierten columnas de esquema canónico.
    }
};
