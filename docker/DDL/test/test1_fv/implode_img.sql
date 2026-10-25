create definer = `test1-fv`@localhost view implode_img as
select `test1_fv`.`product_images`.`company_id`                         AS `company_id`,
       `test1_fv`.`product_images`.`product_id`                         AS `product_id`,
       group_concat(`test1_fv`.`product_images`.`images` separator ',') AS `imploded`
from `test1_fv`.`product_images`
group by `test1_fv`.`product_images`.`product_id`;

-- comment on column implode_img.product_id not supported: PK of Products

