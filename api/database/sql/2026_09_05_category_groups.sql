-- Category groups / subgroups taxonomy (within a category)
-- Also links products to optional group + subgroup.

CREATE TABLE IF NOT EXISTS `category_groups` (
  `group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_status` tinyint(4) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `image` uuid DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`group_id`),
  KEY `category_groups_company_id_foreign` (`company_id`),
  KEY `category_groups_category_id_foreign` (`category_id`),
  KEY `category_groups_image_foreign` (`image`),
  CONSTRAINT `category_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `category_groups_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`cat_id`) ON DELETE CASCADE,
  CONSTRAINT `category_groups_image_foreign` FOREIGN KEY (`image`) REFERENCES `images` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `category_subgroups` (
  `subgroup_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `subgroup_name` varchar(255) NOT NULL,
  `subgroup_status` tinyint(4) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `image` uuid DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`subgroup_id`),
  KEY `category_subgroups_company_id_foreign` (`company_id`),
  KEY `category_subgroups_group_id_foreign` (`group_id`),
  KEY `category_subgroups_image_foreign` (`image`),
  CONSTRAINT `category_subgroups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `category_subgroups_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `category_groups` (`group_id`) ON DELETE CASCADE,
  CONSTRAINT `category_subgroups_image_foreign` FOREIGN KEY (`image`) REFERENCES `images` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product optional link to category group / subgroup
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'category_group_id'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `products`
    ADD COLUMN `category_group_id` bigint(20) unsigned DEFAULT NULL AFTER `unit_id`,
    ADD COLUMN `category_subgroup_id` bigint(20) unsigned DEFAULT NULL AFTER `category_group_id`,
    ADD KEY `products_category_group_id_foreign` (`category_group_id`),
    ADD KEY `products_category_subgroup_id_foreign` (`category_subgroup_id`),
    ADD CONSTRAINT `products_category_group_id_foreign` FOREIGN KEY (`category_group_id`) REFERENCES `category_groups` (`group_id`) ON DELETE SET NULL,
    ADD CONSTRAINT `products_category_subgroup_id_foreign` FOREIGN KEY (`category_subgroup_id`) REFERENCES `category_subgroups` (`subgroup_id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
