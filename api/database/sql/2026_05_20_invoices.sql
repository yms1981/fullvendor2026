-- Facturas (MySQL / MariaDB). subscription_id referencia plan.plan_id o la PK de subscriptions (subscription_row_id).
-- Corregido respecto a borrador con sintaxis T-SQL, comillas erróneas y VARCHAR(MAX).

CREATE TABLE IF NOT EXISTS `invoices` (
  `invoice_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doc_number` varchar(50) DEFAULT NULL,
  `subscription_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `date` datetime DEFAULT NULL,
  `date_expired` datetime DEFAULT NULL,
  `paid` int DEFAULT NULL,
  `days` tinyint unsigned DEFAULT NULL COMMENT 'Días de periodo o flag; ajuste tipo si usa otro criterio',
  `issuing_entity` varchar(50) DEFAULT NULL,
  `address_entity` text,
  `country_entity` varchar(50) DEFAULT NULL,
  `state_entity` varchar(50) DEFAULT NULL,
  `city_entity` varchar(50) DEFAULT NULL COMMENT 'Corregido nombre (antes city:entitty)',
  `concept` text,
  `invoice_paid` decimal(12,2) DEFAULT NULL,
  `debt` decimal(12,2) DEFAULT NULL,
  `stripe_id` varchar(255) DEFAULT NULL,
  `stripe_doc` varchar(512) DEFAULT NULL,
  `stripe_url` varchar(1024) DEFAULT NULL,
  `stripe_pdf` varchar(1024) DEFAULT NULL,
  `stripe_status` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`invoice_id`),
  KEY `invoices_company_id` (`company_id`),
  KEY `invoices_subscription_id` (`subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;