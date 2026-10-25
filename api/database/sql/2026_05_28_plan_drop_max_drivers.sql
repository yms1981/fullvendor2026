-- Elimina columna legada plan.max_Drivers.
ALTER TABLE `plan`
  DROP COLUMN IF EXISTS `max_Drivers`;
