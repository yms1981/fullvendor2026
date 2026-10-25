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
        DB::statement("CREATE OR REPLACE VIEW `images_products` AS select `pi`.`img_id` AS `img_id`,`pi`.`images` AS `images`,`pi`.`created` AS `created`,`p`.`pro_id` AS `pro_id`,`p`.`company_id` AS `company_id`,`pi`.`imageBlob` AS `imageBlob` from (`products` `p` left join `product_images` `pi` on(`p`.`pro_id` = `pi`.`product_id`)) order by `p`.`pro_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `images_products`");
    }
};
