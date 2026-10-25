-- Script para eliminar la columna cat_id de la tabla catalog
-- Esta columna ya no se usa, ahora se utiliza catalog_category para las relaciones

-- Verificar que la tabla catalog_category existe y tiene datos
-- Antes de ejecutar este script, asegúrate de haber migrado todos los datos de cat_id a catalog_category

-- Eliminar la columna cat_id de la tabla catalog
ALTER TABLE `catalog` DROP COLUMN `cat_id`;

-- Nota: Si necesitas mantener los datos históricos, puedes hacer un backup primero:
-- CREATE TABLE catalog_backup AS SELECT * FROM catalog;
-- O exportar solo cat_id antes de eliminarlo:
-- SELECT catalog_id, cat_id INTO OUTFILE '/tmp/catalog_cat_id_backup.csv' FROM catalog WHERE cat_id IS NOT NULL AND cat_id != '';

