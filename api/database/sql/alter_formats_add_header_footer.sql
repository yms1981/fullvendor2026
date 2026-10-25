-- Script para agregar las columnas format_header y format_footer a la tabla formats
-- Ejecutar solo si la tabla formats ya existe pero no tiene estas columnas

ALTER TABLE `formats` 
ADD COLUMN IF NOT EXISTS `format_header` TEXT NULL COMMENT 'Encabezado del formato de impresión' AFTER `format_description`,
ADD COLUMN IF NOT EXISTS `format_footer` TEXT NULL COMMENT 'Pie de página del formato de impresión' AFTER `format_header`;

