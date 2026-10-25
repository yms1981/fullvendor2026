-- Tabla plan (antes subscriptions). PK: plan_id (antes subscription_id).
-- Migración Laravel: 2026_05_14_120000_rename_subscriptions_to_plan_table.php
-- PK renombrada: 2026_05_21_100000_rename_plan_subscription_id_to_plan_id.php
-- Idiomas: language1_id / language2_id (2026_05_15_100000_plan_language1_language2_columns.php)
-- Estado binario: 2026_05_16_120000_plan_status_tinyint_enabled.php

CREATE TABLE IF NOT EXISTS `plan` (
  `plan_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language1_id` int NOT NULL DEFAULT 0,
  `language2_id` int NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `billing_period` varchar(20) NOT NULL COMMENT 'monthly | annual',
  `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1 enabled 0 disabled',
  `max_clients` int unsigned NOT NULL DEFAULT 0,
  `max_orders` int unsigned NOT NULL DEFAULT 0,
  `max_users` int unsigned NOT NULL DEFAULT 0,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`plan_id`),
  KEY `plan_language1_id` (`language1_id`),
  KEY `plan_language2_id` (`language2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
