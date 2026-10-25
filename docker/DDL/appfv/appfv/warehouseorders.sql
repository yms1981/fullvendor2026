CREATE OR REPLACE VIEW `warehouseorders` AS
SELECT
    YEAR(`c`.`created`) AS `ano_id`,
    MONTH(`c`.`created`) AS `mes_id`,
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
    CAST(NULL AS CHAR) AS `order_comments`,
    `c`.`status_id` AS `order_status`,
    `c`.`delivery_status` AS `delivery_status`,
    `c`.`discount` AS `discount`,
    `c`.`discount_type` AS `discount_type`,
    `c`.`created` AS `created`,
    `c`.`updated` AS `updated`,
    `c`.`approved_by` AS `approved_by`,
    CAST(NULL AS CHAR) AS `delivery_notes`,
    `c`.`warehouse_user_id` AS `warehouse_user_id`,
    `c`.`warehouse_assign_date` AS `warehouse_assign_date`,
    `c`.`source` AS `source`,
    CAST(NULL AS CHAR) AS `internal_notes`,
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
    COALESCE(
        NULLIF(TRIM(CONCAT(COALESCE(`u`.`first_name`, ''), ' ', COALESCE(`u`.`last_name`, ''))), ''),
        NULLIF(TRIM(`u`.`name`), ''),
        NULLIF(TRIM(`w`.`name`), ''),
        ''
    ) AS `warehouse_name`
FROM `orders` `c`
INNER JOIN `status_orders` `b` ON `b`.`id_status` = `c`.`status_id`
LEFT JOIN `warehouse_users` `a` ON `a`.`user_id` = `c`.`warehouse_user_id`
LEFT JOIN `users` `u` ON `u`.`id` = `a`.`user_id`
LEFT JOIN `warehouses` `w` ON `w`.`id` = `c`.`warehouse_id`;
