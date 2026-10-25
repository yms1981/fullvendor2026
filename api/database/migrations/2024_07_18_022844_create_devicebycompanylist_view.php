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
        //DB::statement("CREATE OR REPLACE VIEW `devicebycompanylist` AS select `a`.`deviceinfo` AS `deviceinfo`,`a`.`so` AS `so`,`a`.`version` AS `version`,`a`.`created` AS `created`,`a`.`updated_at` AS `updated_at`,`a`.`versionname` AS `versionname`,`b`.`company_id` AS `company_id` from (`deviceinfos` `a` left join `devicesCompanytemp` `b` on(`b`.`deviceinfo` = `a`.`deviceinfo`))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `devicebycompanylist`");
    }
};
