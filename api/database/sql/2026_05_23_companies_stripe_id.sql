-- Cliente Stripe por empresa (customer id devuelto por la API de Stripe).
-- Migración Laravel: 2026_05_23_100000_add_companies_stripe_id_column.php

ALTER TABLE `companies`
  ADD COLUMN `stripe_id` VARCHAR(255) NULL DEFAULT NULL AFTER `email`;
