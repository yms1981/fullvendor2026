-- Alinea `subscriptions` con \Stripe\Subscription:
-- IDs, periodos, trials, cancelación, currency, collection_method y created.
ALTER TABLE `subscriptions`
  ADD COLUMN IF NOT EXISTS `stripe_subscription_id` VARCHAR(255) NULL DEFAULT NULL AFTER `notes`,
  ADD COLUMN IF NOT EXISTS `stripe_status` VARCHAR(64) NULL DEFAULT NULL AFTER `stripe_subscription_id`,
  ADD COLUMN IF NOT EXISTS `stripe_price_id` VARCHAR(255) NULL DEFAULT NULL AFTER `stripe_status`,
  ADD COLUMN IF NOT EXISTS `stripe_currency` VARCHAR(8) NULL DEFAULT NULL AFTER `stripe_price_id`,
  ADD COLUMN IF NOT EXISTS `stripe_collection_method` VARCHAR(32) NULL DEFAULT NULL AFTER `stripe_currency`,
  ADD COLUMN IF NOT EXISTS `stripe_cancel_at_period_end` TINYINT(1) NOT NULL DEFAULT 0 AFTER `stripe_collection_method`,
  ADD COLUMN IF NOT EXISTS `stripe_current_period_start` DATETIME NULL DEFAULT NULL AFTER `stripe_cancel_at_period_end`,
  ADD COLUMN IF NOT EXISTS `stripe_current_period_end`   DATETIME NULL DEFAULT NULL AFTER `stripe_current_period_start`,
  ADD COLUMN IF NOT EXISTS `stripe_trial_start`          DATETIME NULL DEFAULT NULL AFTER `stripe_current_period_end`,
  ADD COLUMN IF NOT EXISTS `stripe_trial_end`            DATETIME NULL DEFAULT NULL AFTER `stripe_trial_start`,
  ADD COLUMN IF NOT EXISTS `stripe_cancel_at`            DATETIME NULL DEFAULT NULL AFTER `stripe_trial_end`,
  ADD COLUMN IF NOT EXISTS `stripe_canceled_at`          DATETIME NULL DEFAULT NULL AFTER `stripe_cancel_at`,
  ADD COLUMN IF NOT EXISTS `stripe_ended_at`             DATETIME NULL DEFAULT NULL AFTER `stripe_canceled_at`,
  ADD COLUMN IF NOT EXISTS `stripe_latest_invoice_id`         VARCHAR(255) NULL DEFAULT NULL AFTER `stripe_ended_at`,
  ADD COLUMN IF NOT EXISTS `stripe_default_payment_method_id` VARCHAR(255) NULL DEFAULT NULL AFTER `stripe_latest_invoice_id`,
  ADD COLUMN IF NOT EXISTS `stripe_created_at`           DATETIME NULL DEFAULT NULL AFTER `stripe_default_payment_method_id`;
