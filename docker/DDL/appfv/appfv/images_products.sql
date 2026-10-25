create definer = appfv@localhost view images_products as
select `pi`.`img_id`    AS `img_id`,
       `pi`.`images`    AS `images`,
       `pi`.`created`   AS `created`,
       `p`.`pro_id`     AS `pro_id`,
       `p`.`company_id` AS `company_id`,
       `pi`.`imageBlob` AS `imageBlob`
from (`appfv`.`products` `p` left join `appfv`.`product_images` `pi` on (`p`.`pro_id` = `pi`.`product_id`))
order by `p`.`pro_id`;

