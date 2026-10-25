create definer = appfv@`%` view orders_categories as
select `od`.`order_category_id` AS `order_category_id`,
       `od`.`category_id`       AS `category_id`,
       `od`.`catalog_id`        AS `catalog_id`,
       `od`.`company_id`        AS `company_id`,
       `od`.`order`             AS `order`,
       `ca`.`catalog_name`      AS `catalog_name`
from (`appfv`.`order_categories` `od` join `appfv`.`catalog` `ca` on (`ca`.`catalog_id` = `od`.`catalog_id`));

