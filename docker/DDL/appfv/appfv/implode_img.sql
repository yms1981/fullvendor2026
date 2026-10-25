create definer = appfv@localhost view implode_img as
select `appfv`.`product_images`.`company_id`                         AS `company_id`,
       `appfv`.`product_images`.`product_id`                         AS `product_id`,
       group_concat(`appfv`.`product_images`.`images` separator ',') AS `imploded`
from `appfv`.`product_images`
group by `appfv`.`product_images`.`product_id`;

-- comment on column implode_img.product_id not supported: PK of Products

