create definer = appfv@localhost view images_count as
select `appfv`.`product_images`.`company_id`    AS `company_id`,
       `appfv`.`product_images`.`product_id`    AS `product_id`,
       count(`appfv`.`product_images`.`img_id`) AS `cImg`
from `appfv`.`product_images`
group by `appfv`.`product_images`.`product_id`
order by `appfv`.`product_images`.`product_id`;

-- comment on column images_count.product_id not supported: PK of Products

