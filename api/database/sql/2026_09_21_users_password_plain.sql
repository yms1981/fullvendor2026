-- ============================================================================
-- 2026_09_21_users_password_plain.sql
--
-- Guarda la contraseña en texto plano para mostrarla en la edición de usuario.
-- El login sigue usando `users.password` (hash bcrypt).
-- ============================================================================

ALTER TABLE `users`
    ADD COLUMN `password_plain` VARCHAR(255) NULL DEFAULT NULL AFTER `password`;
