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
        //DB::statement("CREATE OR REPLACE VIEW `images_default` AS select `pi`.`images` AS `images`,`p`.`pro_id` AS `pro_id` from (`products` `p` left join `product_images` `pi` on(`p`.`pro_id` = `pi`.`product_id`)) where `pi`.`company_id` = 3 and (`pi`.`img_default` = 1 or `pi`.`images` is null) group by `p`.`pro_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `images_default`");
    }
};
