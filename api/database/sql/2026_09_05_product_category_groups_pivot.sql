-- Product ↔ category groups / subgroups (many-to-many)

CREATE TABLE IF NOT EXISTS `product_category_group` (
  `product_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_id`,`group_id`),
  KEY `product_category_group_group_id_foreign` (`group_id`),
  CONSTRAINT `product_category_group_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`) ON DELETE CASCADE,
  CONSTRAINT `product_category_group_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `category_groups` (`group_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_category_subgroup` (
  `product_id` bigint(20) unsigned NOT NULL,
  `subgroup_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_id`,`subgroup_id`),
  KEY `product_category_subgroup_subgroup_id_foreign` (`subgroup_id`),
  CONSTRAINT `product_category_subgroup_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`) ON DELETE CASCADE,
  CONSTRAINT `product_category_subgroup_subgroup_id_foreign` FOREIGN KEY (`subgroup_id`) REFERENCES `category_subgroups` (`subgroup_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
