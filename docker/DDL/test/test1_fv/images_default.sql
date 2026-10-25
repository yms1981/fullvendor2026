create definer = `test1-fv`@localhost view images_default as
select `pi`.`images` AS `images`, `p`.`pro_id` AS `pro_id`
from (`test1_fv`.`products` `p` left join `test1_fv`.`product_images` `pi` on (`p`.`pro_id` = `pi`.`product_id`))
where `pi`.`company_id` = 3
  and (`pi`.`img_default` = 1 or `pi`.`images` is null)
group by `p`.`pro_id`;

