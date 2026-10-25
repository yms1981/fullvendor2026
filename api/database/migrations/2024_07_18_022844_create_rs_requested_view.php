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
        DB::statement("CREATE OR REPLACE VIEW `rs_requested` AS select count(`od`.`product_id`) AS `requested`,`od`.`product_id` AS `product_id`,`p`.`name` AS `name`,sum(`od`.`qty`) AS `qty`,`o`.`company_id` AS `company_id`,`o`.`customer_id` AS `customer_id` from ((`orders` `o` join `order_details` `od` on(`o`.`order_id` = `od`.`order_id`)) join `products` `p` on(`od`.`product_id` = `p`.`pro_id`)) where `o`.`order_status` = 12 group by `od`.`product_id`,`o`.`company_id`,`o`.`customer_id`, `p`.`name` order by `o`.`company_id`,`o`.`customer_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `rs_requested`");
    }
};
