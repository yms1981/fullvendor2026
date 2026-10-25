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
        //DB::statement("CREATE OR REPLACE VIEW `category_count` AS select `prodSeparados`.`company_id` AS `company_id`,count(`prodSeparados`.`pro_id`) AS `items`,`prodSeparados`.`category_value` AS `category_value` from `prodSeparados` group by `prodSeparados`.`company_id`,`prodSeparados`.`category_value`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `category_count`");
    }
};
