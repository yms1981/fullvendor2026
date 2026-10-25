create definer = `test1-fv`@localhost view img_nodata as
select `b`.`pro_id` AS `pro_id`, `a`.`product_id` AS `product_id`, `a`.`sku` AS `sku`, `a`.`company_id` AS `company_id`
from (`test1_fv`.`product_images` `a` left join `test1_fv`.`products` `b`
      on (`b`.`sku` = `a`.`sku` and `b`.`company_id` = `a`.`company_id`))
where `a`.`product_id` = 0;

-- comment on column img_nodata.product_id not supported: PK of Products

