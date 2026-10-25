create definer = appfv@localhost view rs_orders2 as
select `p`.`pro_id`               AS `pro_id`,
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
from (((((`appfv`.`products` `p` join `appfv`.`categories` `cat`
          on (find_in_set(`cat`.`cat_id`, `p`.`category_id`))) join `appfv`.`catalog` `c`
         on (concat(',', `p`.`category_id`, ',') regexp
             concat(',(', replace(`c`.`cat_id`, ',', '|'), '),'))) left join `appfv`.`images_count` `img`
        on (`p`.`pro_id` = `img`.`product_id`)) left join `appfv`.`implode_img` `imp`
       on (`imp`.`product_id` = `p`.`pro_id`)) left join `appfv`.`product_images` `pi`
      on (`p`.`pro_id` = `pi`.`product_id`))
where `c`.`company_id` = 3
  and `c`.`catalog_name` = 'FullVendor Catalog'
  and `p`.`status` = 1
  and `cat`.`category_status` = 1
  and `p`.`name` is not null
  and `p`.`sale_price` > 0;

-- comment on column rs_orders2.category_id not supported: PK of categories

-- comment on column rs_orders2.notify_minimum_stock not supported: 1-Yes 0-No

-- comment on column rs_orders2.status not supported: 0-InActive 1-Active

