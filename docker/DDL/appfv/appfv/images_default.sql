create definer = appfv@localhost view images_default as
select `pi`.`images` AS `images`, `p`.`pro_id` AS `pro_id`
from (`appfv`.`products` `p` left join `appfv`.`product_images` `pi` on (`p`.`pro_id` = `pi`.`product_id`))
where `pi`.`company_id` = 3
  and (`pi`.`img_default` = 1 or `pi`.`images` is null)
group by `p`.`pro_id`;

