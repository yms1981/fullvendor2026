create definer = `test1-fv`@localhost view inventory_orders as
select `c`.`tipo_d`                                   AS `tipo_d`,
       `c`.`order_id`                                 AS `order_id`,
       `c`.`order_number`                             AS `order_number`,
       `c`.`company_id`                               AS `company_id`,
       `c`.`user_id`                                  AS `user_id`,
       `c`.`language_id`                              AS `language_id`,
       `c`.`customer_id`                              AS `customer_id`,
       `c`.`payment_method`                           AS `payment_method`,
       `c`.`payment_status`                           AS `payment_status`,
       `c`.`transaction_id`                           AS `transaction_id`,
       `c`.`order_comments`                           AS `order_comments`,
       `c`.`order_status`                             AS `order_status`,
       `c`.`delivery_status`                          AS `delivery_status`,
       `c`.`discount`                                 AS `discount`,
       `c`.`discount_type`                            AS `discount_type`,
       `c`.`created`                                  AS `created`,
       `c`.`updated`                                  AS `updated`,
       `c`.`approved_by`                              AS `approved_by`,
       `c`.`delivery_notes`                           AS `delivery_notes`,
       `c`.`warehouse_user_id`                        AS `warehouse_user_id`,
       `c`.`warehouse_assign_date`                    AS `warehouse_assign_date`,
       `c`.`source`                                   AS `source`,
       `c`.`internal_notes`                           AS `internal_notes`,
       `c`.`token_pass`                               AS `token_pass`,
       `c`.`date_confirmed`                           AS `date_confirmed`,
       `c`.`send_email_order`                         AS `send_email_order`,
       `c`.`date_rejected_email`                      AS `date_rejected_email`,
       `c`.`date_expired_email`                       AS `date_expired_email`,
       `c`.`expiration_days`                          AS `expiration_days`,
       `c`.`amount`                                   AS `amount`,
       `c`.`discount_a`                               AS `discount_a`,
       `c`.`total_amount`                             AS `total_amount`,
       `c`.`amount_delivered`                         AS `amount_delivered`,
       `c`.`discount_delivered`                       AS `discount_delivered`,
       `c`.`total_delivered`                          AS `total_delivered`,
       concat(`u`.`first_name`, ' ', `u`.`last_name`) AS `business_name`,
       `u`.`email`                                    AS `name`,
       `b`.`name_status_spanish`                      AS `name_status_spanish`,
       `b`.`name_status_english`                      AS `name_status_english`,
       `b`.`color`                                    AS `color`
from ((`test1_fv`.`orders` `c` join `test1_fv`.`status_orders` `b`
       on (`b`.`cod_status` = `c`.`order_status`)) join `test1_fv`.`warehouse_users` `u`
      on (`u`.`user_id` = `c`.`warehouse_user_id`))
where `c`.`tipo_d` = 'I';

-- comment on column inventory_orders.user_id not supported: Pk of vendors

-- comment on column inventory_orders.language_id not supported: PK of languages

-- comment on column inventory_orders.customer_id not supported: PK of customers

-- comment on column inventory_orders.payment_method not supported: 0-CashOn Delivery, 1-Payment Gateway

-- comment on column inventory_orders.payment_status not supported: 0-Due, 1-Paid, 2-Refund, 3-Settled

-- comment on column inventory_orders.order_status not supported: 0-Placed, 1-Dispatched, 2-Delivered, 3-Return Request, 4-Returned, 5-Received, 6-Cancelled,7-Approved,
8-Warehouse Assigned,
9-Partially Completed,
10-Warehouse Completed,
11-Order Completed,
12-Rejected

-- comment on column inventory_orders.delivery_status not supported: 1=Full Delivered, 0=Partial Delivered

-- comment on column inventory_orders.discount_type not supported: 1 = in %, 2 = in $

-- comment on column inventory_orders.warehouse_user_id not supported: PK of warehouse_users

