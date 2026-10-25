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
       // DB::statement("CREATE OR REPLACE VIEW `prodSeparados` AS select `subquery`.`pro_id` AS `pro_id`,`subquery`.`category_value` AS `category_value`,`subquery`.`company_id` AS `company_id` from (select `productsListadoActivo`.`pro_id` AS `pro_id`,`productsListadoActivo`.`company_id` AS `company_id`,trim(substring_index(substring_index(`productsListadoActivo`.`category_id`,',',`numbers`.`n`),',',-1)) AS `category_value` from (`productsListadoActivo` join (select `a`.`n` + `b`.`n` * 10 + 1 AS `n` from ((select 0 AS `n` union all select 1 AS `1` union all select 2 AS `2` union all select 3 AS `3` union all select 4 AS `4` union all select 5 AS `5` union all select 6 AS `6` union all select 7 AS `7` union all select 8 AS `8` union all select 9 AS `9`) `a` join (select 0 AS `n` union all select 1 AS `1` union all select 2 AS `2` union all select 3 AS `3` union all select 4 AS `4` union all select 5 AS `5` union all select 6 AS `6` union all select 7 AS `7` union all select 8 AS `8` union all select 9 AS `9`) `b`) order by `a`.`n` + `b`.`n` * 10 + 1) `numbers`) where `numbers`.`n` <= 1 + (octet_length(`productsListadoActivo`.`category_id`) - octet_length(replace(`productsListadoActivo`.`category_id`,',','')))) `subquery` group by `subquery`.`company_id`,`subquery`.`pro_id`,`subquery`.`category_value`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `prodSeparados`");
    }
};
