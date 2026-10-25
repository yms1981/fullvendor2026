-- Ejecutar en MariaDB/MySQL si aparece error "Unknown column 'state_label' in 'SET'"
-- (equivalente a: cd api && php artisan schema:sync --force)
-- Si la columna ya existe, el ALTER fallará; en ese caso no hace falta repetir.

ALTER TABLE `addresses` ADD COLUMN `state_label` VARCHAR(255) NULL AFTER `state_id`;
ALTER TABLE `addresses` ADD COLUMN `city_label` VARCHAR(255) NULL AFTER `city_id`;
