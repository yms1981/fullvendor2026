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
        DB::statement("CREATE OR REPLACE VIEW `pro_repetidos` AS select `products`.`company_id` AS `company_id`,`products`.`sku` AS `sku`,count(`products`.`pro_id`) AS `cuenta` from `products` group by `products`.`sku`,`products`.`company_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `pro_repetidos`");
    }
};
