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
        $sql = "
        CREATE OR REPLACE VIEW `monto_cart` AS
        SELECT 
            `d`.`cart_id` AS `cart_id`,
            `d`.`created` AS `created`,
            CONCAT(`c`.`name`, ' ', `c`.`business_name`) AS `customer`,
            CONCAT(`c`.`discount`, '%') AS `discount`,
            SUM(`d`.`sale_price` * `d`.`qty`) AS `sub_total`,
            SUM(`d`.`sale_price` * `d`.`qty`) * `c`.`discount` / 100 AS `discount_total`,
            SUM(`d`.`sale_price` * `d`.`qty`) - (SUM(`d`.`sale_price` * `d`.`qty`) * `c`.`discount` / 100) AS `total`,
            `cart`.`catalog_id` AS `catalog_id`,
            `cart`.`company_id` AS `company_id`,
            `cart`.`type_cart` AS `type_cart`,
            `cart`.`status_cart` AS `status_cart`,
            `cart`.`created` AS `date_created`
        FROM `detail_car` `d`
        INNER JOIN `customers` `c` ON `c`.`customer_id` = `d`.`customer_id`
        LEFT JOIN `catalog_cart` `cart` ON `cart`.`cart_id` = `d`.`cart_id`
        GROUP BY 
            `d`.`token_car`,
            `d`.`cart_id`,
            `d`.`created`,
            `c`.`name`,
            `c`.`business_name`,
            `c`.`discount`,
            `cart`.`catalog_id`,
            `cart`.`company_id`,
            `cart`.`type_cart`,
            `cart`.`status_cart`,
            `cart`.`created`
        ";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `monto_cart`");
    }
};

