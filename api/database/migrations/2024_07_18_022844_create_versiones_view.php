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
        DB::statement("CREATE OR REPLACE VIEW `versiones` AS select `a`.`id` AS `id`,`a`.`company_id` AS `company_id`,`b`.`fecha` AS `fecha`,`b`.`updatenumber` AS `version` from (`version` `a` join `app_updates` `b` on(`b`.`ID` = `a`.`id`)) order by `a`.`company_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `versiones`");
    }
};
