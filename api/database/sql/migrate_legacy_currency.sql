-- Migración manual de respaldo. Preferir:
--   php artisan schema:legacy-audit --apply
--   php artisan schema:sync --force
--
-- Comprobar estructura actual:
--   SHOW CREATE TABLE currency;
--
-- Si tiene `currency_id` AUTO_INCREMENT y NO tiene `id`, aplicar este script
-- en una ventana de mantenimiento (ajustar company_id / FKs según entorno).

SET FOREIGN_KEY_CHECKS = 0;

-- 1) Tabla temporal con el esquema nuevo
CREATE TABLE IF NOT EXISTS `currency_new` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `code` varchar(10) NOT NULL DEFAULT '',
  `symbol` varchar(10) NOT NULL DEFAULT '',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `country` varchar(100) DEFAULT NULL,
  `currency` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Copiar datos desde legacy (currency_id → id, currency → name)
INSERT INTO `currency_new` (
  `id`, `name`, `code`, `symbol`, `company_id`, `status`, `country`, `currency`, `created_at`
)
SELECT
  `currency_id`,
  COALESCE(NULLIF(`currency`, ''), NULLIF(`code`, ''), 'Legacy'),
  COALESCE(NULLIF(`code`, ''), ''),
  COALESCE(NULLIF(`symbol`, ''), ''),
  `company_id`,
  COALESCE(`status`, 1),
  `country`,
  `currency`,
  CASE WHEN `created` IS NOT NULL THEN `created` ELSE NULL END
FROM `currency`;

-- 3) Sustituir tabla (descomentar tras verificar INSERT)
-- DROP TABLE `currency`;
-- RENAME TABLE `currency_new` TO `currency`;

SET FOREIGN_KEY_CHECKS = 1;

-- 4) Tras renombrar, volver a ejecutar:
--   php artisan schema:sync --force
--   php artisan schema:audit
