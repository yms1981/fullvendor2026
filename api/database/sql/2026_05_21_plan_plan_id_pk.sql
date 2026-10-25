-- Alinear PK de `plan`: subscription_id → plan_id.
-- Ejecutar solo si existe `subscription_id` y no existe `plan_id`.
-- Migración Laravel: 2026_05_21_100000_rename_plan_subscription_id_to_plan_id.php

ALTER TABLE `plan`
  CHANGE `subscription_id` `plan_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
