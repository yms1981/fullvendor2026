-- Limpia filas soft-deleted de order_categories que duplicaban el sidebar del catálogo público.
-- Tras re-sync del catálogo FullVendor se hacía delete() (soft) + create(), acumulando N copias.
DELETE FROM `order_categories` WHERE `deleted_at` IS NOT NULL;
