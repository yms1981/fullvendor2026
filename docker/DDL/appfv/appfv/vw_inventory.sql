create definer = appfv@localhost view vw_inventory as
select `od`.`company_id`                          AS `company_id`,
       `odt`.`product_id`                         AS `product_id`,
       `p`.`sku`                                  AS `sku`,
       `p`.`name`                                 AS `name`,
       sum(`odt`.`qty` * -1)                      AS `Inventory`,
       avg(`odt`.`sale_price`)                    AS `sale_price`,
       sum(`odt`.`qty` * `odt`.`sale_price` * -1) AS `amount_inventory`
from ((`appfv`.`order_details` `odt` join `appfv`.`orders` `od`
       on (`od`.`order_id` = `odt`.`order_id`)) join `appfv`.`products` `p` on (`p`.`pro_id` = `odt`.`product_id`))
group by `od`.`company_id`, `odt`.`product_id`
order by `od`.`company_id`, `odt`.`product_id`;

-- comment on column vw_inventory.product_id not supported: PK of products

