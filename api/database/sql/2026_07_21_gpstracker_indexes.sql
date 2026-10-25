-- Índices para consultas de ruta GPS por seller y fecha
ALTER TABLE `gpstracker`
  ADD INDEX `idx_gpstracker_company_user_created` (`company_id`, `user_id`, `created`);
