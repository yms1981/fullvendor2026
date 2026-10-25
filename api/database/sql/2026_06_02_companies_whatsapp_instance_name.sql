-- Nombre legible de la instancia de WhatsApp para cada compañía.
-- `use_whatsapp` (BIT(1)) es el flag "Use WhatsApp"; este campo guarda el nombre de la instancia.

ALTER TABLE `companies`
  ADD COLUMN `whatsapp_instance_name` VARCHAR(255) NULL DEFAULT NULL AFTER `use_whatsapp`;
