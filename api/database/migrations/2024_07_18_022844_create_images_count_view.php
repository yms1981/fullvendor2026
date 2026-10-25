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
        //DB::statement("CREATE OR REPLACE VIEW `images_count` AS select `product_images`.`company_id` AS `company_id`,`product_images`.`product_id` AS `product_id`,count(`product_images`.`img_id`) AS `cImg` from `product_images` group by `product_images`.`product_id` order by `product_images`.`product_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `images_count`");
    }
};
