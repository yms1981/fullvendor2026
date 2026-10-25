-- Regla: en órdenes documento (tipo_d = 'D'), order_details.qty debe ser positiva.
-- No tocar tipo_d = 'I' (inventario usa qty negativa).
-- Preferible ejecutar vía: php artisan order-details:ensure-positive-qty-for-d

UPDATE order_details od
INNER JOIN orders o ON o.order_id = od.order_id
SET od.qty = ABS(od.qty)
WHERE o.tipo_d = 'D'
  AND od.qty < 0
  AND od.deleted_at IS NULL;
