-- Contadores diarios de envíos email / SMS / WhatsApp por company_id (Telecom / catálogo).
CREATE TABLE IF NOT EXISTS `company_messaging_daily` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `stat_date` date NOT NULL,
  `email_count` int(10) unsigned NOT NULL DEFAULT 0,
  `sms_count` int(10) unsigned NOT NULL DEFAULT 0,
  `whatsapp_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_messaging_daily_company_date_unique` (`company_id`,`stat_date`),
  KEY `company_messaging_daily_stat_date_index` (`stat_date`),
  CONSTRAINT `company_messaging_daily_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
