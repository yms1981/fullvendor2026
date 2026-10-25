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
        DB::statement("CREATE OR REPLACE VIEW `vw_inventory` AS select `od`.`company_id` AS `company_id`,`odt`.`product_id` AS `product_id`,`p`.`sku` AS `sku`,`p`.`name` AS `name`,sum(`odt`.`qty` * -1) AS `Inventory`,avg(`odt`.`sale_price`) AS `sale_price`,sum(`odt`.`qty` * `odt`.`sale_price` * -1) AS `amount_inventory` from ((`order_details` `odt` join `orders` `od` on(`od`.`order_id` = `odt`.`order_id`)) join `products` `p` on(`p`.`pro_id` = `odt`.`product_id`)) group by `od`.`company_id`,`odt`.`product_id`,p.sku,p.name order by `od`.`company_id`,`odt`.`product_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `vw_inventory`");
    }
};
