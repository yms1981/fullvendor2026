-- Permiso de eCommerce a nivel de plan (BIT(1), coherente con `status`).
-- 0 = sin permiso, 1 = con permiso. Por defecto 0.

ALTER TABLE `plan`
  ADD COLUMN `ecommerce_permission` BIT(1) NOT NULL DEFAULT b'0' AFTER `max_users`;
