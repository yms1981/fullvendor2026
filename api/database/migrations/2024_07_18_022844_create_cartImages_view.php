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
        DB::statement("CREATE OR REPLACE VIEW `cartImages` AS select `b`.`company_id` AS `company_id`,`a`.`img_id` AS `img_id`,`b`.`pro_id` AS `pro_id` from (`product_images` `a` join `products` `b` on(`b`.`pro_id` = `a`.`product_id`)) order by `b`.`company_id`,`b`.`product_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `cartImages`");
    }
};
