<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->modifyInventoryOrdersView();
        $this->modifyMyOrdersView();
        $this->modifyRsRequestedView();
        $this->modifyWarehouseOrdersView();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropView('inventory_orders');
        $this->dropView('myorders');
        $this->dropView('rs_requested');
        $this->dropView('warehouseorders');
    }

    /**
     * @param string $table
     * @return void
     */
    public function dropView(string $table): void
    {
        DB::statement("DROP VIEW IF EXISTS `$table`");
    }

    /**
     * @return void
     */
    public function modifyInventoryOrdersView(): void
    {
        $this->dropView('inventory_orders');

        DB::statement("CREATE OR REPLACE VIEW `inventory_orders`
            AS select `c`.`tipo_d` AS `tipo_d`,
            `c`.`order_id` AS `order_id`,
            `c`.`order_number` AS `order_number`,
            `c`.`company_id` AS `company_id`,
            `c`.`seller_id` AS `user_id`,
            `c`.`language_id` AS `language_id`,
            `c`.`customer_id` AS `customer_id`,
            `c`.`payment_method` AS `payment_method`,
            `c`.`payment_status` AS `payment_status`,
            `c`.`transaction_id` AS `transaction_id`,
            `c`.`order_comments` AS `order_comments`,
            `c`.`status_id` AS `order_status`,
            `c`.`delivery_status` AS `delivery_status`,
            `c`.`discount` AS `discount`,
            `c`.`discount_type` AS `discount_type`,
            `c`.`created` AS `created`,
            `c`.`updated` AS `updated`,
            `c`.`approved_by` AS `approved_by`,
            `c`.`delivery_notes` AS `delivery_notes`,
            `c`.`warehouse_user_id` AS `warehouse_user_id`,
            `c`.`warehouse_assign_date` AS `warehouse_assign_date`,
            `c`.`source` AS `source`,
            `c`.`internal_notes` AS `internal_notes`,
            `c`.`token_pass` AS `token_pass` ,
            `c`.`date_confirmed` AS `date_confirmed`,
            `c`.`send_email_order` AS `send_email_order`,
            `c`.`date_rejected_email` AS `date_rejected_email`,
            `c`.`date_expired_email` AS `date_expired_email`,
            `c`.`expiration_days` AS `expiration_days`,
            `c`.`amount` AS `amount`,
            `c`.`discount_a` AS `discount_a`,
            `c`.`total_amount` AS `total_amount`,
            `c`.`amount_delivered` AS `amount_delivered`,
            `c`.`discount_delivered` AS `discount_delivered`,
            `c`.`total_delivered` AS `total_delivered`,
            concat(`u`.`first_name`,' ',`u`.`last_name`) AS `business_name`,
            `u`.`email` AS `name`,
            `b`.`name_status_spanish` AS `name_status_spanish`,
            `b`.`name_status_english` AS `name_status_english`,
            `b`.`color` AS `color` from ((`orders` `c` join `status_orders` `b` on(`b`.`id_status` = `c`.`status_id`)) join `warehouse_users` `u` on(`u`.`user_id` = `c`.`warehouse_user_id`)) where `c`.`tipo_d` = 'I'");
    }

    /**
     * @return void
     */
    public function modifyMyOrdersView(): void
    {
        $this->dropView('myorders');

        DB::statement("CREATE OR REPLACE VIEW `myorders`
            AS select ifnull(`o`.`updated`,`o`.`created`) AS `fechaorden`,
            `o`.`tipo_d` AS `tipo_d`,
            `o`.`order_id` AS `order_id`,
            `o`.`order_number` AS `order_number`,
            `o`.`company_id` AS `company_id`,
            `o`.`seller_id` AS `user_id`,
            `o`.`language_id` AS `language_id`,
            `o`.`customer_id` AS `customer_id`,
            `o`.`payment_method` AS `payment_method`,
            `o`.`payment_status` AS `payment_status`,
            `o`.`transaction_id` AS `transaction_id`,
            `o`.`order_comments` AS `order_comments`,
            `o`.`status_id` AS `order_status`,
            `o`.`delivery_status` AS `delivery_status`,
            `o`.`discount` AS `discount`,
            `o`.`discount_type` AS `discount_type`,
            `o`.`created` AS `created`,
            `o`.`updated` AS `updated`,
            `o`.`approved_by` AS `approved_by`,
            `o`.`delivery_notes` AS `delivery_notes`,
            `o`.`warehouse_user_id` AS `warehouse_user_id`,
            `o`.`warehouse_assign_date` AS `warehouse_assign_date`,
            `o`.`source` AS `source`,
            `o`.`internal_notes` AS `internal_notes`,
            `o`.`token_pass` AS `token_pass`,
            `o`.`date_confirmed` AS `date_confirmed`,
            `o`.`send_email_order` AS `send_email_order`,
            `o`.`date_rejected_email` AS `date_rejected_email`,
            `o`.`date_expired_email` AS `date_expired_email`,
            `o`.`expiration_days` AS `expiration_days`,
            `o`.`amount` AS `amount`,
            `o`.`discount_a` AS `discount_a`,
            `o`.`total_amount` AS `total_amount`,
            `o`.`amount_delivered` AS `amount_delivered`,
            `o`.`discount_delivered` AS `discount_delivered`,
            `o`.`total_delivered` AS `total_delivered`,
            `o`.`portal_vendor` AS `portal_vendor`,
            `o`.`virified_byportal` AS `virified_byportal`,
            `o`.`id_kor` AS `id_kor`,
            `o`.`cart_id` AS `cart_id`,
            `o`.`catalog_id` AS `catalog_id`,
            `o`.`update_bybackend` AS `update_bybackend`,
            `o`.`discount_type_since_movil` AS `discount_type_since_movil`,
            `o`.`discount_movil` AS `discount_movil`,
            `o`.`leido` AS `leido` from `orders` `o`");
    }

    /**
     * @return void
     */
    public function modifyRsRequestedView(): void
    {
        $this->dropView('rs_requested');

        DB::statement("CREATE OR REPLACE VIEW `rs_requested`
            AS select count(`od`.`product_id`) AS `requested`,
            `od`.`product_id` AS `product_id`,
            `p`.`name` AS `name`,
            sum(`od`.`qty`) AS `qty`,
            `o`.`company_id` AS `company_id`,
            `o`.`customer_id` AS `customer_id` from ((`orders` `o` join `order_details` `od` on(`o`.`order_id` = `od`.`order_id`)) join `products` `p` on(`od`.`product_id` = `p`.`pro_id`)) where `o`.`status_id` = 14 group by `od`.`product_id`,
            `o`.`company_id`,
            `o`.`customer_id`,
            `p`.`name` order by `o`.`company_id`,
            `o`.`customer_id`");
    }

    /**
     * @return void
     */
    public function modifyWarehouseOrdersView(): void
    {
        $this->dropView('warehouseorders');

        DB::statement("CREATE OR REPLACE VIEW `warehouseorders`
            AS select year(`c`.`created`) AS `ano_id`,
            month(`c`.`created`) AS `mes_id`,
            `c`.`tipo_d` AS `tipo_d`,
            `c`.`order_id` AS `order_id`,
            `c`.`order_number` AS `order_number`,
            `c`.`company_id` AS `company_id`,
            `c`.`seller_id` AS `user_id`,
            `c`.`language_id` AS `language_id`,
            `c`.`customer_id` AS `customer_id`,
            `c`.`payment_method` AS `payment_method`,
            `c`.`payment_status` AS `payment_status`,
            `c`.`transaction_id` AS `transaction_id`,
            `c`.`order_comments` AS `order_comments`,
            `c`.`status_id` AS `order_status`,
            `c`.`delivery_status` AS `delivery_status`,
            `c`.`discount` AS `discount`,
            `c`.`discount_type` AS `discount_type`,
            `c`.`created` AS `created`,
            `c`.`updated` AS `updated`,
            `c`.`approved_by` AS `approved_by`,
            `c`.`delivery_notes` AS `delivery_notes`,
            `c`.`warehouse_user_id` AS `warehouse_user_id`,
            `c`.`warehouse_assign_date` AS `warehouse_assign_date`,
            `c`.`source` AS `source`,
            `c`.`internal_notes` AS `internal_notes`,
            `c`.`token_pass` AS `token_pass`,
            `c`.`expiration_days` AS `expiration_days`,
            `c`.`amount` AS `amount`,
            `c`.`discount_a` AS `discount_a`,
            `c`.`total_amount` AS `total_amount`,
            `c`.`amount_delivered` AS `amount_delivered`,
            `c`.`discount_delivered` AS `discount_delivered`,
            `c`.`total_delivered` AS `total_delivered`,
            `b`.`name_status_spanish` AS `name_status_spanish`,
            `b`.`name_status_english` AS `name_status_english`,
            `b`.`color` AS `color`,
            concat(`a`.`first_name`,' ',`a`.`last_name`) AS `warehouse_name` from ((`orders` `c` join `status_orders` `b` on(`b`.`id_status` = `c`.`status_id`)) left join `warehouse_users` `a` on(`a`.`user_id` = `c`.`warehouse_user_id`))");
    }
};
