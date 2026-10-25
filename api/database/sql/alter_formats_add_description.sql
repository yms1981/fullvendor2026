-- Script para agregar la columna format_description si no existe
-- Ejecutar solo si la tabla formats ya existe pero no tiene la columna format_description

ALTER TABLE `formats` 
ADD COLUMN IF NOT EXISTS `format_description` TEXT NULL COMMENT 'Descripción del formato' AFTER `format_name`;

