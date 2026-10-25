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
        DB::statement("CREATE OR REPLACE VIEW `devicesCompanytemp` AS select `gpstracker`.`company_id` AS `company_id`,`gpstracker`.`deviceinfo` AS `deviceinfo` from `gpstracker` group by `gpstracker`.`company_id`,`gpstracker`.`deviceinfo`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `devicesCompanytemp`");
    }
};
