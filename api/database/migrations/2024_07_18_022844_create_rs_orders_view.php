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
        DB::statement("CREATE OR REPLACE VIEW `rs_orders` AS select `o`.`order_id` AS `order_id`,sum(`o`.`amount_sales`) AS `amount`,sum(`o`.`discount_amount`) AS `discount`,sum(`o`.`amount_delivered`) AS `amount_delivered`,sum(`o`.`discount_delivered`) AS `discount_delivered`,sum(`o`.`total_amount`) AS `total_amount`,sum(`o`.`total_delivered`) AS `total_delivered` from `order_details` `o` group by `o`.`order_id` order by `o`.`order_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `rs_orders`");
    }
};
