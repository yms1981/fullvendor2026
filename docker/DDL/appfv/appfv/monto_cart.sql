create definer = appfv@`%` view monto_cart as
select `d`.`cart_id`                                                                                AS `cart_id`,
       `d`.`created`                                                                                AS `created`,
       concat(`c`.`name`, ' ', `c`.`business_name`)                                                 AS `customer`,
       concat(`c`.`discount`, '%')                                                                  AS `discount`,
       sum(`d`.`sale_price` * `d`.`qty`)                                                            AS `sub_total`,
       sum(`d`.`sale_price` * `d`.`qty`) * `c`.`discount` / 100                                     AS `discount_total`,
       sum(`d`.`sale_price` * `d`.`qty`) - sum(`d`.`sale_price` * `d`.`qty`) * `c`.`discount` / 100 AS `total`,
       `cart`.`catalog_id`                                                                          AS `catalog_id`,
       `cart`.`company_id`                                                                          AS `company_id`,
       `cart`.`type_cart`                                                                           AS `type_cart`,
       `cart`.`status_cart`                                                                         AS `status_cart`,
       `cart`.`created`                                                                             AS `date_created`
from ((`appfv`.`detail_car` `d` join `appfv`.`customers` `c`
       on (`c`.`customer_id` = `d`.`customer_id`)) left join `appfv`.`catalog_cart` `cart`
      on (`cart`.`cart_id` = `d`.`cart_id`))
group by `d`.`token_car`;

