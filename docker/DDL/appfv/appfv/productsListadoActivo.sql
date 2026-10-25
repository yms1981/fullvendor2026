create definer = appfv@`%` view productsListadoActivo as
select `appfv`.`products`.`pro_id`               AS `pro_id`,
       `appfv`.`products`.`product_id`           AS `product_id`,
       `appfv`.`products`.`language_id`          AS `language_id`,
       `appfv`.`products`.`company_id`           AS `company_id`,
       `appfv`.`products`.`warehouse_user_id`    AS `warehouse_user_id`,
       `appfv`.`products`.`sku`                  AS `sku`,
       `appfv`.`products`.`name`                 AS `name`,
       `appfv`.`products`.`sale_price`           AS `sale_price`,
       `appfv`.`products`.`category_id`          AS `category_id`,
       `appfv`.`products`.`barcode`              AS `barcode`,
       `appfv`.`products`.`barcode_images`       AS `barcode_images`,
       `appfv`.`products`.`tags`                 AS `tags`,
       `appfv`.`products`.`descriptions`         AS `descriptions`,
       `appfv`.`products`.`unit_type`            AS `unit_type`,
       `appfv`.`products`.`stock`                AS `stock`,
       `appfv`.`products`.`minimum_stock`        AS `minimum_stock`,
       `appfv`.`products`.`notify_minimum_stock` AS `notify_minimum_stock`,
       `appfv`.`products`.`currency_type`        AS `currency_type`,
       `appfv`.`products`.`fob_price`            AS `fob_price`,
       `appfv`.`products`.`purchase_price`       AS `purchase_price`,
       `appfv`.`products`.`locations`            AS `locations`,
       `appfv`.`products`.`created_by`           AS `created_by`,
       `appfv`.`products`.`created_on`           AS `created_on`,
       `appfv`.`products`.`modified_on`          AS `modified_on`,
       `appfv`.`products`.`status`               AS `status`,
       `appfv`.`products`.`Img`                  AS `Img`,
       `appfv`.`products`.`imgURL`               AS `imgURL`,
       `appfv`.`products`.`simgURL`              AS `simgURL`,
       `appfv`.`products`.`id_kor`               AS `id_kor`,
       `appfv`.`products`.`cat_id`               AS `cat_id`,
       `appfv`.`products`.`cats_id`              AS `cats_id`,
       `appfv`.`products`.`sell_with_inventory`  AS `sell_with_inventory`
from `appfv`.`products`
where `appfv`.`products`.`status` = 1;

-- comment on column productsListadoActivo.category_id not supported: PK of categories

-- comment on column productsListadoActivo.notify_minimum_stock not supported: 1-Yes 0-No

-- comment on column productsListadoActivo.status not supported: 0-InActive 1-Active

