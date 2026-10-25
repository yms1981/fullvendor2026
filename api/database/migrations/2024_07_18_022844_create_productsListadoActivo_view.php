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
        DB::statement("CREATE OR REPLACE VIEW `productsListadoActivo` AS select `products`.`pro_id` AS `pro_id`,`products`.`product_id` AS `product_id`,`products`.`language_id` AS `language_id`,`products`.`company_id` AS `company_id`,`products`.`warehouse_user_id` AS `warehouse_user_id`,`products`.`sku` AS `sku`,`products`.`name` AS `name`,`products`.`sale_price` AS `sale_price`,`products`.`category_id` AS `category_id`,`products`.`barcode` AS `barcode`,`products`.`barcode_images` AS `barcode_images`,`products`.`tags` AS `tags`,`products`.`descriptions` AS `descriptions`,`products`.`unit_type` AS `unit_type`,`products`.`stock` AS `stock`,`products`.`minimum_stock` AS `minimum_stock`,`products`.`notify_minimum_stock` AS `notify_minimum_stock`,`products`.`currency_type` AS `currency_type`,`products`.`fob_price` AS `fob_price`,`products`.`purchase_price` AS `purchase_price`,`products`.`locations` AS `locations`,`products`.`created_by` AS `created_by`,`products`.`created_on` AS `created_on`,`products`.`modified_on` AS `modified_on`,`products`.`status` AS `status`,`products`.`Img` AS `Img`,`products`.`imgURL` AS `imgURL`,`products`.`simgURL` AS `simgURL`,`products`.`id_kor` AS `id_kor`,`products`.`cat_id` AS `cat_id`,`products`.`cats_id` AS `cats_id`,`products`.`sell_with_inventory` AS `sell_with_inventory` from `products` where `products`.`status` = 1");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `productsListadoActivo`");
    }
};
