create definer = appfv@`%` view myorders as
select ifnull(`o`.`updated`, `o`.`created`) AS `fechaorden`,
       `o`.`tipo_d`                         AS `tipo_d`,
       `o`.`order_id`                       AS `order_id`,
       `o`.`order_number`                   AS `order_number`,
       `o`.`company_id`                     AS `company_id`,
       `o`.`user_id`                        AS `user_id`,
       `o`.`language_id`                    AS `language_id`,
       `o`.`customer_id`                    AS `customer_id`,
       `o`.`payment_method`                 AS `payment_method`,
       `o`.`payment_status`                 AS `payment_status`,
       `o`.`transaction_id`                 AS `transaction_id`,
       `o`.`order_comments`                 AS `order_comments`,
       `o`.`order_status`                   AS `order_status`,
       `o`.`delivery_status`                AS `delivery_status`,
       `o`.`discount`                       AS `discount`,
       `o`.`discount_type`                  AS `discount_type`,
       `o`.`created`                        AS `created`,
       `o`.`updated`                        AS `updated`,
       `o`.`approved_by`                    AS `approved_by`,
       `o`.`delivery_notes`                 AS `delivery_notes`,
       `o`.`warehouse_user_id`              AS `warehouse_user_id`,
       `o`.`warehouse_assign_date`          AS `warehouse_assign_date`,
       `o`.`source`                         AS `source`,
       `o`.`internal_notes`                 AS `internal_notes`,
       `o`.`token_pass`                     AS `token_pass`,
       `o`.`date_confirmed`                 AS `date_confirmed`,
       `o`.`send_email_order`               AS `send_email_order`,
       `o`.`date_rejected_email`            AS `date_rejected_email`,
       `o`.`date_expired_email`             AS `date_expired_email`,
       `o`.`expiration_days`                AS `expiration_days`,
       `o`.`amount`                         AS `amount`,
       `o`.`discount_a`                     AS `discount_a`,
       `o`.`total_amount`                   AS `total_amount`,
       `o`.`amount_delivered`               AS `amount_delivered`,
       `o`.`discount_delivered`             AS `discount_delivered`,
       `o`.`total_delivered`                AS `total_delivered`,
       `o`.`portal_vendor`                  AS `portal_vendor`,
       `o`.`virified_byportal`              AS `virified_byportal`,
       `o`.`id_kor`                         AS `id_kor`,
       `o`.`cart_id`                        AS `cart_id`,
       `o`.`catalog_id`                     AS `catalog_id`,
       `o`.`update_bybackend`               AS `update_bybackend`,
       `o`.`discount_type_since_movil`      AS `discount_type_since_movil`,
       `o`.`discount_movil`                 AS `discount_movil`,
       `o`.`leido`                          AS `leido`,
       `o`.`new_address_json`               AS `new_address_json`
from `appfv`.`orders` `o`;

-- comment on column myorders.user_id not supported: Pk of vendors

-- comment on column myorders.language_id not supported: PK of languages

-- comment on column myorders.customer_id not supported: PK of customers

-- comment on column myorders.payment_method not supported: 0-CashOn Delivery, 1-Payment Gateway

-- comment on column myorders.payment_status not supported: 0-Due, 1-Paid, 2-Refund, 3-Settled

-- comment on column myorders.order_status not supported: 0-Placed, 1-Dispatched, 2-Delivered, 3-Return Request, 4-Returned, 5-Received, 6-Cancelled,7-Approved,
8-Warehouse Assigned,
9-Partially Completed,
10-Warehouse Completed,
11-Order Completed,
12-Rejected

-- comment on column myorders.delivery_status not supported: 1=Full Delivered, 0=Partial Delivered

-- comment on column myorders.discount_type not supported: 1 = in %, 2 = in $

-- comment on column myorders.warehouse_user_id not supported: PK of warehouse_users

