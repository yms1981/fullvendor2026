-- OBSOLETO: esquema antiguo de “planes” en tabla `subscriptions` (PK `subscription_id`).
-- El producto actual usa: tabla `plan` para planes y tabla `subscriptions` para vínculos empresa↔plan
-- con PK `subscription_row_id` (ver api/database/sql/2026_05_17_subscriptions_company_plan.sql).
-- No ejecute este CREATE en bases ya migradas: puede chocar con la tabla `subscriptions` nueva.

-- (histórico) Tabla subscriptions — migración inicial antes del renombre a plan.

-- Tabla subscriptions (planes de suscripción admin).
-- Sin FK a `languages`: errno 150 suele aparecer si signed/unsigned o tipo de
-- `language_id` no coincide exactamente con la tabla existente (o si `languages`
-- no es InnoDB). El panel admin valida el idioma en aplicación.
-- Índice para consultas por idioma.

CREATE TABLE IF NOT EXISTS `subscriptions` (
  `subscription_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_id` int NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `billing_period` varchar(20) NOT NULL COMMENT 'monthly | annual',
  `lifecycle_status` varchar(20) NOT NULL COMMENT 'active | expired | cancelled',
  `max_clients` int unsigned NOT NULL DEFAULT 0,
  `max_orders` int unsigned NOT NULL DEFAULT 0,
  `max_users` int unsigned NOT NULL DEFAULT 0,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`subscription_id`),
  KEY `subscriptions_language_id` (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
