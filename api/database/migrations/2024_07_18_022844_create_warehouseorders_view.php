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
        DB::statement("CREATE OR REPLACE VIEW `warehouseorders` AS select year(`c`.`created`) AS `ano_id`,month(`c`.`created`) AS `mes_id`,`c`.`tipo_d` AS `tipo_d`,`c`.`order_id` AS `order_id`,`c`.`order_number` AS `order_number`,`c`.`company_id` AS `company_id`,`c`.`user_id` AS `user_id`,`c`.`language_id` AS `language_id`,`c`.`customer_id` AS `customer_id`,`c`.`payment_method` AS `payment_method`,`c`.`payment_status` AS `payment_status`,`c`.`transaction_id` AS `transaction_id`,`c`.`order_comments` AS `order_comments`,`c`.`order_status` AS `order_status`,`c`.`delivery_status` AS `delivery_status`,`c`.`discount` AS `discount`,`c`.`discount_type` AS `discount_type`,`c`.`created` AS `created`,`c`.`updated` AS `updated`,`c`.`approved_by` AS `approved_by`,`c`.`delivery_notes` AS `delivery_notes`,`c`.`warehouse_user_id` AS `warehouse_user_id`,`c`.`warehouse_assign_date` AS `warehouse_assign_date`,`c`.`source` AS `source`,`c`.`internal_notes` AS `internal_notes`,`c`.`token_pass` AS `token_pass`,`c`.`expiration_days` AS `expiration_days`,`c`.`amount` AS `amount`,`c`.`discount_a` AS `discount_a`,`c`.`total_amount` AS `total_amount`,`c`.`amount_delivered` AS `amount_delivered`,`c`.`discount_delivered` AS `discount_delivered`,`c`.`total_delivered` AS `total_delivered`,`b`.`name_status_spanish` AS `name_status_spanish`,`b`.`name_status_english` AS `name_status_english`,`b`.`color` AS `color`,concat(`a`.`first_name`,' ',`a`.`last_name`) AS `warehouse_name` from ((`orders` `c` join `status_orders` `b` on(`b`.`cod_status` = `c`.`order_status`)) left join `warehouse_users` `a` on(`a`.`user_id` = `c`.`warehouse_user_id`))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `warehouseorders`");
    }
};
