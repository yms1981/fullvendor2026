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
        DB::statement("CREATE OR REPLACE VIEW `Valid_Img` AS select `b`.`pro_id` AS `pro_id`,`a`.`img_id` AS `img_id`,`a`.`product_id` AS `product_id`,`a`.`language_id` AS `language_id`,`a`.`images` AS `images`,`a`.`created` AS `created`,`a`.`updated` AS `updated`,`a`.`Img` AS `Img`,`a`.`sImgURL` AS `sImgURL`,`a`.`ImgURL` AS `ImgURL`,`a`.`id_kor` AS `id_kor`,`a`.`Description` AS `Description`,`a`.`company_id` AS `company_id`,`a`.`sku` AS `sku`,`a`.`img_default` AS `img_default` from (`product_images` `a` left join `products` `b` on(`b`.`sku` = `a`.`sku` and `b`.`company_id` = `a`.`company_id`))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `Valid_Img`");
    }
};
