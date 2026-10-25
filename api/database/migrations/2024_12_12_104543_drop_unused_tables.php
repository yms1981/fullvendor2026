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
        if(Schema::hasTable('FullVendorCatalog1'))
            Schema::drop('FullVendorCatalog1');
        if(Schema::hasTable('FullVendorCatalog1_3'))
            Schema::drop('FullVendorCatalog1_3');
        if(Schema::hasTable('FullVendorCatalog2'))
            Schema::drop('FullVendorCatalog2');
        if(Schema::hasTable('FullVendorCatalog2_3'))
            Schema::drop('FullVendorCatalog2_3');
        if(Schema::hasTable('Tabla1'))
            Schema::drop('Tabla1');
        if(Schema::hasTable('Tabla2'))
            Schema::drop('Tabla2');



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // nothing to recover
    }
};
