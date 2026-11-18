-- customer_emails: flag por correo para envío de catálogo (marcado por defecto).
ALTER TABLE `customer_emails`
  ADD COLUMN `send_catalog` tinyint(1) NOT NULL DEFAULT 1 AFTER `is_primary`;
