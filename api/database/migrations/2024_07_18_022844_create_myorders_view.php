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
        DB::statement("CREATE OR REPLACE VIEW `myorders` AS select ifnull(`o`.`updated`,`o`.`created`) AS `fechaorden`,`o`.`tipo_d` AS `tipo_d`,`o`.`order_id` AS `order_id`,`o`.`order_number` AS `order_number`,`o`.`company_id` AS `company_id`,`o`.`user_id` AS `user_id`,`o`.`language_id` AS `language_id`,`o`.`customer_id` AS `customer_id`,`o`.`payment_method` AS `payment_method`,`o`.`payment_status` AS `payment_status`,`o`.`transaction_id` AS `transaction_id`,`o`.`order_comments` AS `order_comments`,`o`.`order_status` AS `order_status`,`o`.`delivery_status` AS `delivery_status`,`o`.`discount` AS `discount`,`o`.`discount_type` AS `discount_type`,`o`.`created` AS `created`,`o`.`updated` AS `updated`,`o`.`approved_by` AS `approved_by`,`o`.`delivery_notes` AS `delivery_notes`,`o`.`warehouse_user_id` AS `warehouse_user_id`,`o`.`warehouse_assign_date` AS `warehouse_assign_date`,`o`.`source` AS `source`,`o`.`internal_notes` AS `internal_notes`,`o`.`token_pass` AS `token_pass`,`o`.`date_confirmed` AS `date_confirmed`,`o`.`send_email_order` AS `send_email_order`,`o`.`date_rejected_email` AS `date_rejected_email`,`o`.`date_expired_email` AS `date_expired_email`,`o`.`expiration_days` AS `expiration_days`,`o`.`amount` AS `amount`,`o`.`discount_a` AS `discount_a`,`o`.`total_amount` AS `total_amount`,`o`.`amount_delivered` AS `amount_delivered`,`o`.`discount_delivered` AS `discount_delivered`,`o`.`total_delivered` AS `total_delivered`,`o`.`portal_vendor` AS `portal_vendor`,`o`.`virified_byportal` AS `virified_byportal`,`o`.`id_kor` AS `id_kor`,`o`.`cart_id` AS `cart_id`,`o`.`catalog_id` AS `catalog_id`,`o`.`update_bybackend` AS `update_bybackend`,`o`.`discount_type_since_movil` AS `discount_type_since_movil`,`o`.`discount_movil` AS `discount_movil`,`o`.`leido` AS `leido` from `orders` `o`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `myorders`");
    }
};
