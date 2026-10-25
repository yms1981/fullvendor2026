-- Issuing entity (singleton config for Stripe settings / invoices).

CREATE TABLE IF NOT EXISTS `issuing_entity` (
  `entity_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_name` varchar(100) DEFAULT NULL,
  `entity_address` text,
  `entity_country` varchar(50) DEFAULT NULL,
  `entity_state` varchar(50) DEFAULT NULL,
  `entity_city` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
