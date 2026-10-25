-- Correos adicionales vinculados a un cliente (además de customers.email = primario).
CREATE TABLE IF NOT EXISTS `customer_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_emails_customer_email_unique` (`customer_id`, `email`),
  KEY `customer_emails_customer_id_index` (`customer_id`),
  CONSTRAINT `customer_emails_customer_id_foreign`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
