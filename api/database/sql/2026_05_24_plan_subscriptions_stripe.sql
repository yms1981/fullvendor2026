-- Plan: Price ID y metadatos Stripe (legado stripeld).
ALTER TABLE `plan`
  ADD COLUMN IF NOT EXISTS `stripeld` varchar(255) DEFAULT NULL AFTER `price`,
  ADD COLUMN IF NOT EXISTS `stripeName` varchar(255) DEFAULT NULL AFTER `stripeld`,
  ADD COLUMN IF NOT EXISTS `stripe_status` varchar(64) DEFAULT NULL AFTER `stripeName`;

-- Suscripción empresa↔plan: ID Stripe sub_…
ALTER TABLE `subscriptions`
  ADD COLUMN IF NOT EXISTS `stripe_subscription_id` varchar(255) DEFAULT NULL AFTER `notes`,
  ADD COLUMN IF NOT EXISTS `stripe_status` varchar(64) DEFAULT NULL AFTER `stripe_subscription_id`;
