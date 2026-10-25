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
        DB::statement("CREATE OR REPLACE VIEW `oDetailList` AS select `o`.`detail_id` AS `detail_id`,`o`.`order_id` AS `order_id`,`o`.`product_id` AS `product_id`,`p`.`sku` AS `sku`,`p`.`name` AS `name`,`o`.`qty` AS `qty`,`o`.`delivered_quantity` AS `delivered_quantity`,`o`.`discount` AS `discount`,`o`.`discount_type` AS `discount_type`,`o`.`sale_price` AS `sale_price`,`o`.`fob_price` AS `fob_price`,`o`.`purchase_price` AS `purchase_price`,`o`.`comment` AS `comment`,`o`.`created` AS `created`,`o`.`amount_sales` AS `amount_sales`,`o`.`discount_amount` AS `discount_amount`,`o`.`total_amount` AS `total_amount`,`o`.`amount_delivered` AS `amount_delivered`,`o`.`discount_delivered` AS `discount_delivered`,`o`.`total_delivered` AS `total_delivered` from ((`order_details` `o` join `orders` `c` on(`c`.`order_id` = `o`.`order_id`)) join `products` `p` on(`p`.`pro_id` = `o`.`product_id`))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `oDetailList`");
    }
};
