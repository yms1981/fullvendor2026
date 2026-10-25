<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       // DB::statement("CREATE OR REPLACE VIEW `get_imagesproducts` AS select `p`.`company_id` AS `company_id`,`p`.`product_id` AS `product_id`,count(`p`.`product_id`) AS `qty_images`,group_concat(concat(`p`.`images`) order by `p`.`img_order` ASC separator ',') AS `group_images` from `product_images` `p` where `p`.`company_id` is not null and `p`.`product_id` is true group by `p`.`company_id`,`p`.`product_id` order by `p`.`img_id` desc");
       // DB::statement("CREATE OR REPLACE VIEW `images_count` AS select `product_images`.`company_id` AS `company_id`,`product_images`.`product_id` AS `product_id`,count(`product_images`.`img_id`) AS `cImg` from `product_images` group by `product_images`.`product_id` order by `product_images`.`product_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement("CREATE OR REPLACE VIEW `get_imagesproducts` AS select `p`.`company_id` AS `company_id`,`p`.`product_id` AS `product_id`,count(`p`.`product_id`) AS `qty_images`,group_concat(concat(`p`.`images`) order by `p`.`img_order` ASC separator ',') AS `group_images` from `product_images` `p` where `p`.`company_id` is not null and `p`.`product_id` is true group by `p`.`product_id` order by `p`.`img_id` desc");
        Schema::enableForeignKeyConstraints();

    }
};
