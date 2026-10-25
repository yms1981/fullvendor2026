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

        DB::statement("CREATE OR REPLACE VIEW `get_imagesproducts` AS select `p`.`company_id` AS `company_id`,`p`.`product_id` AS `product_id`,count(`p`.`product_id`) AS `qty_images`,group_concat(concat(`p`.`images`) order by `p`.`img_order` ASC separator ',') AS `group_images` from `product_images` `p` where `p`.`company_id` is not null and `p`.`product_id` is true group by `p`.`product_id`, `p`.`company_id` order by `p`.`img_id` desc");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `get_imagesproducts`");
    }
};
