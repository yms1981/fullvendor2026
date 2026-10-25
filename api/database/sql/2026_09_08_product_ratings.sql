-- Rating de productos por empresa (derivado de ventas o valoración estándar)
CREATE TABLE IF NOT EXISTS `product_ratings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 3.5,
  `sales_count` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sales_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `source` varchar(20) NOT NULL DEFAULT 'standard',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_ratings_company_product_unique` (`company_id`,`product_id`),
  KEY `product_ratings_company_id_foreign` (`company_id`),
  KEY `product_ratings_product_id_foreign` (`product_id`),
  KEY `product_ratings_company_rating_index` (`company_id`,`rating`),
  CONSTRAINT `product_ratings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `product_ratings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
