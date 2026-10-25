-- Script para actualizar el campo template de LONGTEXT a VARCHAR(10000)
-- Ejecutar solo si la tabla formats ya existe

ALTER TABLE `formats` 
MODIFY COLUMN `template` VARCHAR(10000) NULL COMMENT 'Plantilla HTML del formato de impresión';

