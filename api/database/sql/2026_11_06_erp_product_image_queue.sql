-- Cola de descarga de imágenes ERP (una a una, con log por archivo).

CREATE TABLE IF NOT EXISTS `erp_product_image_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(120) NOT NULL,
  `image_url` varchar(2048) NOT NULL,
  `url_hash` varchar(64) NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `queued_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `epi_queue_company_product_url_uq` (`company_id`,`product_id`,`url_hash`),
  KEY `epi_queue_status_id_idx` (`status`,`id`),
  KEY `epi_queue_company_status_idx` (`company_id`,`status`,`id`),
  CONSTRAINT `epi_queue_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
