create definer = appfv@localhost view img_nodata as
select `b`.`pro_id` AS `pro_id`, `a`.`product_id` AS `product_id`, `a`.`sku` AS `sku`, `a`.`company_id` AS `company_id`
from (`appfv`.`product_images` `a` left join `appfv`.`products` `b`
      on (`b`.`sku` = `a`.`sku` and `b`.`company_id` = `a`.`company_id`))
where `a`.`product_id` = 0;

-- comment on column img_nodata.product_id not supported: PK of Products

