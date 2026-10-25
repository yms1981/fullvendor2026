# Vista `warehouseorders`

Vista usada por el legado REST (`orderList_post`, etc.) y por AppSalesman `orderList`.

## Problema

Si la vista quedó como stub de columnas `NULL` (sin `FROM orders`), no devuelve filas útiles:

```sql
SELECT NULL AS `ano_id`, NULL AS `mes_id`, ... NULL AS `warehouse_name`
```

Eso ocurre cuando el `CREATE VIEW` antiguo falló por columnas eliminadas (`orders.user_id`, `order_status`, `order_comments`, `warehouse_users.first_name`, etc.) y se sustituyó por un placeholder.

## Definición vigente

Fuente: `api/database/sql/views/warehouseorders.sql` (también en `docker/DDL/appfv/appfv/warehouseorders.sql`).

Adaptaciones al esquema actual:

| Alias vista | Origen actual |
|-------------|----------------|
| `user_id` | `orders.seller_id` |
| `order_status` | `orders.status_id` |
| `order_comments` / `delivery_notes` / `internal_notes` | `NULL` (columnas ya no en `orders`) |
| `warehouse_name` | `users` (vía `warehouse_users`) o `warehouses.name` |
| join estado | `status_orders.id_status = orders.status_id` |

## Aplicar

```bash
# Con schema:sync (incluye vistas del directorio)
php artisan schema:sync --force

# O solo la vista
mysql ... < api/database/sql/views/warehouseorders.sql
```

Comprobar:

```sql
SHOW CREATE VIEW warehouseorders;
SELECT order_id, tipo_d, customer_id, user_id, name_status_spanish, warehouse_name
FROM warehouseorders
WHERE tipo_d = 'D'
ORDER BY created DESC
LIMIT 5;
```
