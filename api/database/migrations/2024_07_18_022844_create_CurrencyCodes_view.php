<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE OR REPLACE VIEW `CurrencyCodes` AS select `currency_formats`.`CurrencyCode` AS `CurrencyCode` from `currency_formats` group by `currency_formats`.`CurrencyCode` order by `currency_formats`.`CurrencyCode`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `CurrencyCodes`");
    }
};
