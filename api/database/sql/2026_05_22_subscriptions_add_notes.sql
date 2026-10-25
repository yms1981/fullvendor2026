-- Añade columna `notes` si la tabla subscriptions existe sin ella (MySQL / MariaDB).
-- La definición canónica con `notes` está en 2026_05_17_subscriptions_company_plan.sql.

ALTER TABLE `subscriptions`
  ADD COLUMN `notes` text DEFAULT NULL AFTER `end_period_date`;
