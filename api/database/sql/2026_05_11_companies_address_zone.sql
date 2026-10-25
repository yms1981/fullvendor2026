-- companies: una sola dirección con nombres simples (sin columnas comercial / despacho duplicadas).
-- Campos: full_address, address, country, state, city, zip_code, zone.
-- Migración Laravel: `2026_05_11_120000_add_companies_address_and_zone_columns` (+ `2026_05_12_090000_add_companies_full_address_column`).
--
-- MariaDB 10.3.2+: ADD COLUMN IF NOT EXISTS (seguro si se ejecuta varias veces).
-- Desde el host con Sail (puerto por defecto 3306 mapeado):
--   docker compose -f api/docker-compose.yml exec mariadb mariadb -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" "${MYSQL_DATABASE}" < api/database/sql/2026_05_11_companies_address_zone.sql
-- O con cliente local:
--   mariadb -h127.0.0.1 -P3306 -uTU_USUARIO -p TU_BASE < api/database/sql/2026_05_11_companies_address_zone.sql

ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `full_address` TEXT NULL;
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `address` TEXT NULL;
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `country` VARCHAR(191) NULL;
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `state` VARCHAR(191) NULL;
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `city` VARCHAR(191) NULL;
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `zip_code` VARCHAR(191) NULL;
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `zone` VARCHAR(191) NULL;

-- Si tu servidor no admite IF NOT EXISTS en ADD COLUMN, usa una a una y omite el error "Duplicate column":
-- ALTER TABLE `companies` ADD COLUMN `full_address` TEXT NULL;
-- ALTER TABLE `companies` ADD COLUMN `address` TEXT NULL;
-- ...
