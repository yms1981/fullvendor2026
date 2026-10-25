create definer = `test1-fv`@localhost view images_products as
select `pi`.`img_id`    AS `img_id`,
       `pi`.`images`    AS `images`,
       `pi`.`created`   AS `created`,
       `p`.`pro_id`     AS `pro_id`,
       `p`.`company_id` AS `company_id`,
       `pi`.`imageBlob` AS `imageBlob`
from (`test1_fv`.`products` `p` left join `test1_fv`.`product_images` `pi` on (`p`.`pro_id` = `pi`.`product_id`))
order by `p`.`pro_id`;

