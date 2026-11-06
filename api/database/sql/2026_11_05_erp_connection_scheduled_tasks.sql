-- ERP connection on companies + scheduled sync tasks
ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `erp_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `zone`,
  ADD COLUMN IF NOT EXISTS `erp_company_code` VARCHAR(100) NULL DEFAULT NULL AFTER `erp_enabled`,
  ADD COLUMN IF NOT EXISTS `erp_url` VARCHAR(500) NULL DEFAULT NULL AFTER `erp_company_code`,
  ADD COLUMN IF NOT EXISTS `erp_company_token` TEXT NULL DEFAULT NULL AFTER `erp_url`;

CREATE TABLE IF NOT EXISTS `company_scheduled_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `task_type` varchar(64) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `frequency` varchar(32) NOT NULL DEFAULT 'manual',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `last_status` varchar(32) DEFAULT NULL,
  `last_message` text DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_scheduled_tasks_company_type_unique` (`company_id`,`task_type`),
  KEY `company_scheduled_tasks_due_index` (`enabled`,`next_run_at`),
  CONSTRAINT `company_scheduled_tasks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
