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
        DB::statement("CREATE OR REPLACE VIEW `monto_cart` AS select `d`.`cart_id` AS `cart_id`,`d`.`created` AS `created`,concat(`c`.`name`,' ',`c`.`business_name`) AS `customer`,concat(`c`.`discount`,'%') AS `discount`,sum(`d`.`sale_price` * `d`.`qty`) AS `sub_total`,sum(`d`.`sale_price` * `d`.`qty`) * `c`.`discount` / 100 AS `discount_total`,sum(`d`.`sale_price` * `d`.`qty`) - sum(`d`.`sale_price` * `d`.`qty`) * `c`.`discount` / 100 AS `total`,`cart`.`catalog_id` AS `catalog_id`,`cart`.`company_id` AS `company_id`,`cart`.`type_cart` AS `type_cart`,`cart`.`status_cart` AS `status_cart`,`cart`.`created` AS `date_created` from ((`detail_car` `d` join `customers` `c` on(`c`.`customer_id` = `d`.`customer_id`)) left join `catalog_cart` `cart` on(`cart`.`cart_id` = `d`.`cart_id`)) group by `d`.`token_car`, `d`.`cart_id`, d.created,c.name,c.business_name,c.discount,cart.catalog_id,cart.company_id,cart.type_cart,cart.status_cart,cart.created");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `monto_cart`");
    }
};
