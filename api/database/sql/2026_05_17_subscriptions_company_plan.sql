-- Suscripciones: vínculo empresa ↔ plan (plan.plan_id).
-- Periodo: start_period_date / end_period_date (migración renombre: 2026_05_19_100000_subscriptions_rename_period_columns.php).
-- Migración Laravel: 2026_05_17_120000_create_subscriptions_company_plan_table.php

CREATE TABLE IF NOT EXISTS `subscriptions` (
  `subscription_row_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL COMMENT 'plan.plan_id',
  `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1 enabled 0 disabled',
  `start_period_date` date DEFAULT NULL,
  `end_period_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`subscription_row_id`),
  KEY `subscriptions_company_id` (`company_id`),
  KEY `subscriptions_plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
