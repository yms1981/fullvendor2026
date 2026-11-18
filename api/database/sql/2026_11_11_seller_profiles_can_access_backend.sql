-- Permiso de perfil vendedor: acceso al panel company (backend) desde /login.
ALTER TABLE `seller_profiles`
  ADD COLUMN `can_access_backend` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=No 1=Yes' AFTER `can_create_customer`;
