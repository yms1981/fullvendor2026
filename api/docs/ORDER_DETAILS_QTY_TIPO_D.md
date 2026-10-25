# Regla: órdenes normales (`tipo_d = D`) — qty, ítems y totales

## Convención

| `orders.tipo_d` | Signo de `order_details.qty` | Motivo |
|-----------------|------------------------------|--------|
| **D** (documento / venta / orden normal) | **Siempre positivo** (`qty > 0`) | En `vw_inventory` el stock usa `sum(qty * -1)`. |
| **I** (inventario) | Negativo (entradas) o placeholder `-1` | **No** aplicar ABS ni borrar por signo. |
| **C** (nota crédito) | Positivo en create | Sin esta sanitización automática. |

## Al entrar a Órdenes

En `OrderController::index`, `list` y `warehouse` (compañía del listado), antes de devolver datos:

1. Identifica órdenes **`tipo_d = D`** de la compañía.
2. Si `qty < 0` → `qty = ABS(qty)` y **recalcula** líneas + cabecera (`amount`, `discount_a`, `total_amount`, delivered_*).
3. Si `qty = 0` (o ≈ 0) → **elimina** el ítem (`forceDelete`).
4. Si la orden queda **sin ítems** → **elimina** la orden.

Servicio: `App\Services\Orders\EnsurePositiveQtyForDocumentOrdersService::sanitizeCompanyDocumentOrders()`.

## Enforcement al guardar

1. `OrderDetail::saving`: ABS si `tipo_d = D` y `qty < 0`.
2. `Order::saved` (alta o cambio a D): ABS de líneas negativas.

## Comando

```bash
# Sanitización completa por compañía (o todas)
php artisan order-details:ensure-positive-qty-for-d --full
php artisan order-details:ensure-positive-qty-for-d --company=1

# Solo contar qty negativas
php artisan order-details:ensure-positive-qty-for-d --dry-run
```

SQL de referencia (solo ABS): `api/database/sql/2026_03_24_order_details_qty_positive_for_tipo_d.sql`
