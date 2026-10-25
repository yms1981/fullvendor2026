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
        DB::statement("CREATE OR REPLACE VIEW `version` AS select max(`app_updates`.`ID`) AS `id`,`app_updates`.`company_id` AS `company_id` from `app_updates` group by `app_updates`.`company_id` order by `app_updates`.`company_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `version`");
    }
};
