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
        DB::statement("CREATE OR REPLACE VIEW `img_nodata` AS select `b`.`pro_id` AS `pro_id`,`a`.`product_id` AS `product_id`,`a`.`sku` AS `sku`,`a`.`company_id` AS `company_id` from (`product_images` `a` left join `products` `b` on(`b`.`sku` = `a`.`sku` and `b`.`company_id` = `a`.`company_id`)) where `a`.`product_id` = 0");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `img_nodata`");
    }
};
