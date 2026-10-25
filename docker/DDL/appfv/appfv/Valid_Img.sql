create definer = appfv@localhost view Valid_Img as
select `b`.`pro_id`      AS `pro_id`,
       `a`.`img_id`      AS `img_id`,
       `a`.`product_id`  AS `product_id`,
       `a`.`language_id` AS `language_id`,
       `a`.`images`      AS `images`,
       `a`.`created`     AS `created`,
       `a`.`updated`     AS `updated`,
       `a`.`Img`         AS `Img`,
       `a`.`sImgURL`     AS `sImgURL`,
       `a`.`ImgURL`      AS `ImgURL`,
       `a`.`id_kor`      AS `id_kor`,
       `a`.`Description` AS `Description`,
       `a`.`company_id`  AS `company_id`,
       `a`.`sku`         AS `sku`,
       `a`.`img_default` AS `img_default`
from (`appfv`.`product_images` `a` left join `appfv`.`products` `b`
      on (`b`.`sku` = `a`.`sku` and `b`.`company_id` = `a`.`company_id`));

-- comment on column Valid_Img.product_id not supported: PK of Products

