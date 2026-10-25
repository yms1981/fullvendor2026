-- Vistas alineadas con product_images + images (post-migración 2025_07_14 / 2025_06_22)

CREATE OR REPLACE VIEW `Valid_Img` AS
SELECT `b`.`pro_id` AS `pro_id`,
       `a`.`img_id` AS `img_id`,
       `a`.`product_id` AS `product_id`,
       NULL AS `language_id`,
       `i`.`path` AS `images`,
       `a`.`created` AS `created`,
       `a`.`updated` AS `updated`,
       NULL AS `Img`,
       `i`.`path` AS `sImgURL`,
       `i`.`path` AS `ImgURL`,
       NULL AS `id_kor`,
       `i`.`original_name` AS `Description`,
       `b`.`company_id` AS `company_id`,
       `b`.`sku` AS `sku`,
       (`b`.`default_image_id` = `a`.`image_id`) AS `img_default`
FROM `product_images` `a`
LEFT JOIN `products` `b` ON `b`.`pro_id` = `a`.`product_id`
LEFT JOIN `images` `i` ON `i`.`uuid` = `a`.`image_id`
WHERE `a`.`deleted_at` IS NULL;

CREATE OR REPLACE VIEW `get_imagesproducts` AS
SELECT `p`.`company_id` AS `company_id`,
       `pi`.`product_id` AS `product_id`,
       COUNT(`pi`.`img_id`) AS `qty_images`,
       GROUP_CONCAT(`i`.`path` ORDER BY `pi`.`img_order` ASC SEPARATOR ',') AS `group_images`
FROM `product_images` `pi`
INNER JOIN `products` `p` ON `p`.`pro_id` = `pi`.`product_id`
LEFT JOIN `images` `i` ON `i`.`uuid` = `pi`.`image_id`
WHERE `pi`.`product_id` IS NOT NULL AND `pi`.`deleted_at` IS NULL
GROUP BY `pi`.`product_id`, `p`.`company_id`;

CREATE OR REPLACE VIEW `images_products` AS
SELECT `pi`.`img_id` AS `img_id`,
       `i`.`path` AS `images`,
       `pi`.`created` AS `created`,
       `p`.`pro_id` AS `pro_id`,
       `p`.`company_id` AS `company_id`,
       NULL AS `imageBlob`
FROM `products` `p`
LEFT JOIN `product_images` `pi` ON `p`.`pro_id` = `pi`.`product_id` AND `pi`.`deleted_at` IS NULL
LEFT JOIN `images` `i` ON `i`.`uuid` = `pi`.`image_id`
ORDER BY `p`.`pro_id`;

CREATE OR REPLACE VIEW `img_nodata` AS
SELECT `b`.`pro_id` AS `pro_id`,
       `a`.`product_id` AS `product_id`,
       `b`.`sku` AS `sku`,
       `b`.`company_id` AS `company_id`
FROM `product_images` `a`
LEFT JOIN `products` `b` ON `b`.`pro_id` = `a`.`product_id`
WHERE `a`.`product_id` IS NULL OR `a`.`product_id` = 0;

CREATE OR REPLACE VIEW `implode_img` AS
SELECT `p`.`company_id` AS `company_id`,
       `pi`.`product_id` AS `product_id`,
       GROUP_CONCAT(COALESCE(`i`.`path`, '') SEPARATOR ',') AS `imploded`
FROM `product_images` `pi`
INNER JOIN `products` `p` ON `p`.`pro_id` = `pi`.`product_id`
LEFT JOIN `images` `i` ON `i`.`uuid` = `pi`.`image_id`
WHERE `pi`.`deleted_at` IS NULL
GROUP BY `pi`.`product_id`, `p`.`company_id`;
