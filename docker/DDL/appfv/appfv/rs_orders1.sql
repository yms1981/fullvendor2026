create definer = appfv@localhost view rs_orders1 as
select `od`.`order`               AS `order`,
       `p`.`pro_id`               AS `pro_id`,
       `p`.`product_id`           AS `product_id`,
       `p`.`language_id`          AS `language_id`,
       `p`.`company_id`           AS `company_id`,
       `p`.`warehouse_user_id`    AS `warehouse_user_id`,
       `p`.`sku`                  AS `sku`,
       `p`.`name`                 AS `name`,
       `p`.`sale_price`           AS `sale_price`,
       `p`.`category_id`          AS `category_id`,
       `p`.`barcode`              AS `barcode`,
       `p`.`barcode_images`       AS `barcode_images`,
       `p`.`tags`                 AS `tags`,
       `p`.`descriptions`         AS `descriptions`,
       `p`.`unit_type`            AS `unit_type`,
       `p`.`stock`                AS `stock`,
       `p`.`minimum_stock`        AS `minimum_stock`,
       `p`.`notify_minimum_stock` AS `notify_minimum_stock`,
       `p`.`currency_type`        AS `currency_type`,
       `p`.`fob_price`            AS `fob_price`,
       `p`.`purchase_price`       AS `purchase_price`,
       `p`.`locations`            AS `locations`,
       `p`.`created_by`           AS `created_by`,
       `p`.`created_on`           AS `created_on`,
       `p`.`modified_on`          AS `modified_on`,
       `p`.`status`               AS `status`,
       `p`.`Img`                  AS `Img`,
       `p`.`imgURL`               AS `imgURL`,
       `p`.`simgURL`              AS `simgURL`,
       `p`.`id_kor`               AS `id_kor`,
       `p`.`cat_id`               AS `cat_id`,
       `p`.`cats_id`              AS `cats_id`,
       `pi`.`images`              AS `images`,
       `img`.`cImg`               AS `cImg`,
       `imp`.`imploded`           AS `implode_img`
from ((((((`appfv`.`order_categories` `od` join `appfv`.`catalog` `catl`
           on (`catl`.`catalog_id` = `od`.`catalog_id`)) join `appfv`.`categories` `c`
          on (`c`.`cat_id` = `od`.`category_id`)) join `appfv`.`products` `p`
         on (find_in_set(`od`.`category_id`, `p`.`category_id`))) left join `appfv`.`images_count` `img`
        on (`p`.`pro_id` = `img`.`product_id`)) left join `appfv`.`implode_img` `imp`
       on (`imp`.`product_id` = `p`.`pro_id`)) left join `appfv`.`product_images` `pi`
      on (`p`.`pro_id` = `pi`.`product_id`))
where `catl`.`catalog_name` = 'FullVendor Catalog'
  and `p`.`status` = 1
  and `c`.`category_status` = 1
  and `p`.`name` is not null
  and `p`.`sale_price` > 0
group by `p`.`pro_id`
order by `od`.`order`, `p`.`name`;

-- comment on column rs_orders1.category_id not supported: PK of categories

-- comment on column rs_orders1.notify_minimum_stock not supported: 1-Yes 0-No

-- comment on column rs_orders1.status not supported: 0-InActive 1-Active

