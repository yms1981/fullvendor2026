-- ECommerce connection flags on companies (profile).
-- Safe to re-run: skip columns that already exist (MariaDB 10.3.3+ IF NOT EXISTS).
ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `ecommerce_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `erp_company_token`;

ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `ecommerce_url` VARCHAR(500) NULL DEFAULT NULL AFTER `ecommerce_enabled`;
