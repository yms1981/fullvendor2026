create definer = appfv@localhost view get_imagesproducts as
select `p`.`company_id`                                                              AS `company_id`,
       `p`.`product_id`                                                              AS `product_id`,
       count(`p`.`product_id`)                                                       AS `qty_images`,
       group_concat(concat(`p`.`images`) order by `p`.`img_order` ASC separator ',') AS `group_images`
from `appfv`.`product_images` `p`
where `p`.`company_id` is not null
  and `p`.`product_id` is true
group by `p`.`product_id`
order by `p`.`img_id` desc;

-- comment on column get_imagesproducts.product_id not supported: PK of Products

