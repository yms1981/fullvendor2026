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
        //DB::statement("CREATE OR REPLACE VIEW `categoryList` AS select `A`.`order` AS `order_id`,`B`.`cat_id` AS `cat_id`,`B`.`category_id` AS `category_id`,`B`.`language_id` AS `language_id`,`B`.`company_id` AS `company_id`,`B`.`category_name` AS `category_name`,`B`.`images` AS `images`,`B`.`category_status` AS `category_status`,`B`.`category_created_at` AS `category_created_at`,`B`.`id_kor` AS `id_kor`,`B`.`category_default` AS `category_default`,ifnull(`D`.`items`,0) AS `items` from (((`order_categories` `A` left join `categories` `B` on(`B`.`cat_id` = `A`.`category_id`)) join `catalog` `C` on(`C`.`catalog_id` = `A`.`catalog_id`)) left join `category_count` `D` on(`D`.`category_value` = `A`.`category_id`)) where `C`.`catalog_name` = 'FullVendor Catalog' and `B`.`category_status` = '1' and ifnull(`D`.`items`,0) > 0 order by `A`.`company_id`,`A`.`order`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `categoryList`");
    }
};
