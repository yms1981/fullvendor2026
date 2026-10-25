create definer = `test1-fv`@localhost view images_count as
select `test1_fv`.`product_images`.`company_id`    AS `company_id`,
       `test1_fv`.`product_images`.`product_id`    AS `product_id`,
       count(`test1_fv`.`product_images`.`img_id`) AS `cImg`
from `test1_fv`.`product_images`
group by `test1_fv`.`product_images`.`product_id`
order by `test1_fv`.`product_images`.`product_id`;

-- comment on column images_count.product_id not supported: PK of Products

