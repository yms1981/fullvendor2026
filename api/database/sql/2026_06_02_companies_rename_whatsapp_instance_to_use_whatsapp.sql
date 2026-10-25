-- Renombrar el flag `whatsapp_instance` (BIT(1)) a `use_whatsapp` para coincidir con el label
-- del checkbox en /admin/company (add y edit). El campo "instance name" sigue siendo
-- `whatsapp_instance_name` (VARCHAR) y la API key `whatsapp_apikey` (VARCHAR).

ALTER TABLE `companies`
  CHANGE COLUMN `whatsapp_instance` `use_whatsapp` BIT(1) NOT NULL DEFAULT b'0';
