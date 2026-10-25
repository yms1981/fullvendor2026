-- Claves Stripe (estado real en BD según verificación):
-- stripe_secret: id, secret (sk_…)
-- stripe_publish: stripeID, secretAPI (legado; puede duplicar la secret key)
-- Fallback: variable de entorno STRIPE_SECRET_KEY

-- Cliente Stripe por empresa (requerido para facturas en Stripe):
-- ALTER TABLE `companies` ADD COLUMN `stripe_id` varchar(255) NULL AFTER `email`;
-- Migración: 2026_05_23_100000_add_companies_stripe_id_column.php
