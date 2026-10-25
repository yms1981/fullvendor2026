-- Flag "Use WhatsApp" + API Key a nivel de compañía.
-- use_whatsapp:    BIT(1) (0 = no usar WhatsApp, 1 = usar WhatsApp). Por defecto 0.
-- whatsapp_apikey: VARCHAR(255) con la API key opcional para la instancia.

ALTER TABLE `companies`
  ADD COLUMN `use_whatsapp` BIT(1) NOT NULL DEFAULT b'0' AFTER `show_semaforo_stock`,
  ADD COLUMN `whatsapp_apikey` VARCHAR(255) NULL DEFAULT NULL AFTER `use_whatsapp`;
