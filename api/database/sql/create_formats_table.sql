-- Tabla genérica para almacenar formatos de impresión
CREATE TABLE IF NOT EXISTS `formats` (
    `format_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `format_name` VARCHAR(255) NOT NULL COMMENT 'Nombre del formato',
    `template` LONGTEXT NULL COMMENT 'Plantilla HTML del formato de impresión',
    `order` INT DEFAULT 0 COMMENT 'Orden de visualización',
    `is_default` TINYINT(1) DEFAULT 0 COMMENT '1 si es el formato por defecto',
    `company_id` INT NULL COMMENT 'ID de la empresa propietaria del formato',
    `format_type` VARCHAR(50) NULL COMMENT 'Tipo de formato: catalog, order, invoice, etc.',
    `related_id` BIGINT NULL COMMENT 'ID del recurso relacionado (catalog_id, order_id, etc.) según format_type',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    
    -- Foreign keys
    CONSTRAINT `fk_formats_company` 
        FOREIGN KEY (`company_id`) 
        REFERENCES `companies` (`company_id`) 
        ON DELETE CASCADE,
    
    -- Índices
    INDEX `idx_formats_company_id` (`company_id`),
    INDEX `idx_formats_format_type` (`format_type`),
    INDEX `idx_formats_related_id` (`related_id`),
    INDEX `idx_formats_is_default` (`is_default`),
    INDEX `idx_formats_deleted_at` (`deleted_at`),
    INDEX `idx_formats_type_related` (`format_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formatos genéricos de impresión';

