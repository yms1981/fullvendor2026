create definer = `test1-fv`@localhost view cartImages as
select `b`.`company_id` AS `company_id`, `a`.`img_id` AS `img_id`, `b`.`pro_id` AS `pro_id`
from (`test1_fv`.`product_images` `a` join `test1_fv`.`products` `b` on (`b`.`pro_id` = `a`.`product_id`))
order by `b`.`company_id`, `b`.`product_id`;

