-- Especificaciones de producto (pares nombre/valor en JSON)
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `specifications` longtext DEFAULT NULL
    COMMENT 'JSON array of product specs [{name,value}]'
    AFTER `descriptions`;
