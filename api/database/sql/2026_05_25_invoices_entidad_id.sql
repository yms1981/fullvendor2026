-- Vincula facturas con entidad emisora (FK opcional).

ALTER TABLE `invoices`
  ADD COLUMN `entidad_id` bigint unsigned DEFAULT NULL COMMENT 'FK entidad_emisora.entidad_id' AFTER `days`,
  ADD KEY `invoices_entidad_id` (`entidad_id`);
