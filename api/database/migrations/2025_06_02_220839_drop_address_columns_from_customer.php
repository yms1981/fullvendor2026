<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $columns = [
        'commercial_address',
        'commercial_country',
        'commercial_state',
        'commercial_city',
        'commercial_zone',
        'commercial_zip_code',
        'dispatch_address',
        'dispatch_country',
        'dispatch_state',
        'dispatch_city',
        'dispatch_zone',
        'dispatch_zip_code',
        'commercial_delivery_address',
    ];

    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (!Schema::hasColumn('customers', $column)) {
                    $table->string($column, 255)->nullable();
                }
            }
        });
    }
};
