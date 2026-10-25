-- Columnas legacy en `sellers` que ya no están en appfv_full_schema.sql
-- (migración 2025_09_08_151802_drop_unused_columns_from_seller).
-- En producción provocan: Field 'Add_customer' Doesn't Have A Default Value
-- al crear un vendedor.

SET @db := DATABASE();

-- add_customer
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sellers' AND COLUMN_NAME = 'add_customer'
);
SET @sql := IF(@exists > 0, 'ALTER TABLE `sellers` DROP COLUMN `add_customer`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- update_customer
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sellers' AND COLUMN_NAME = 'update_customer'
);
SET @sql := IF(@exists > 0, 'ALTER TABLE `sellers` DROP COLUMN `update_customer`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- send_catalog
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sellers' AND COLUMN_NAME = 'send_catalog'
);
SET @sql := IF(@exists > 0, 'ALTER TABLE `sellers` DROP COLUMN `send_catalog`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- unique_id
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sellers' AND COLUMN_NAME = 'unique_id'
);
SET @sql := IF(@exists > 0, 'ALTER TABLE `sellers` DROP COLUMN `unique_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- otp
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sellers' AND COLUMN_NAME = 'otp'
);
SET @sql := IF(@exists > 0, 'ALTER TABLE `sellers` DROP COLUMN `otp`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
