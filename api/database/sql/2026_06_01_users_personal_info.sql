-- ============================================================================
-- 2026_06_01_users_personal_info.sql
--
-- Añade columnas personales/dirección a `users` para los formularios de
-- /company/users (modal y vistas de alta/edición): first_name, last_name,
-- address, city, state, zip_code. Todas opcionales (NULL por defecto).
--
-- Ejecutar manualmente en MariaDB/MySQL si no se aplica via migración
-- Laravel `2026_06_01_120000_add_personal_info_columns_to_users_table`.
-- ============================================================================

ALTER TABLE `users`
    ADD COLUMN `first_name` VARCHAR(100) NULL DEFAULT NULL AFTER `name`,
    ADD COLUMN `last_name`  VARCHAR(100) NULL DEFAULT NULL AFTER `first_name`,
    ADD COLUMN `address`    VARCHAR(255) NULL DEFAULT NULL AFTER `fax`,
    ADD COLUMN `city`       VARCHAR(100) NULL DEFAULT NULL AFTER `address`,
    ADD COLUMN `state`      VARCHAR(100) NULL DEFAULT NULL AFTER `city`,
    ADD COLUMN `zip_code`   VARCHAR(30)  NULL DEFAULT NULL AFTER `state`;
