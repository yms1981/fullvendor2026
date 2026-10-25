# APIs Warehouse (almacén) — `/api/v2/warehouse`

Documentación de la sección **WarehouseOrders** (Swagger tag: `WarehouseOrders`).

UI Swagger: `/api/v2/documentation` (también `/swagger` y `/api-docs`).

Auth: **Bearer Sanctum**.

> No modifica las APIs de ubicación (`/api/v2/order/warehouse/...`). Son endpoints nuevos bajo `/api/v2/warehouse/...`.

---

## Endpoints

| Método | Path | Descripción |
|--------|------|-------------|
| POST | `/api/v2/warehouse/credit-notes` | Crear nota de crédito (`tipo_d=C`; exige `warehouse_user_id`) |
| GET/POST | `/api/v2/warehouse/credit-notes/list` | Listar notas de crédito (`tipo_d=C`) con usuario warehouse |
| POST | `/api/v2/warehouse/set-status` | Cambiar estado de orden (`setstatus_post`) |
| POST | `/api/v2/warehouse/update-delivered-item` | Actualiza cantidad entregada de un ítem |
| POST | `/api/v2/warehouse/update-inventory-status` | Despacho inventario (`updateInventoryStatus_post`) |
| POST | `/api/v2/warehouse/update-inventory-stock` | Qty de línea + estado 16 (`updateInventoryStock_post`) |
| GET/POST | `/api/v2/warehouse/orders/pending` | Órdenes pendientes (cod_status **9**) + ítems |
| GET/POST | `/api/v2/warehouse/orders/completed` | Órdenes terminadas (cod_status **11**, **12**) + ítems |
| GET/POST | `/api/v2/warehouse/orders/inventory` | Órdenes de inventario (`tipo_d = I`) + ítems |
| GET/POST | `/api/v2/warehouse/orders/inventory/with-products` | Inventario + ítems con producto completo y fotos |
| GET/POST | `/api/v2/warehouse/orders/assigned-and-completed` | Assigned (9) + Completed (11/12) con info completa y delivered_* |
| GET/POST | `/api/v2/warehouse/by-warehouse-id/assigned` | Simple: solo `warehouse_id` → assigned + ítems + delivered_* |
| GET/POST | `/api/v2/warehouse/by-warehouse-id/completed` | Simple: solo `warehouse_id` → completed + ítems + delivered_* |
| GET/POST | `/api/v2/warehouse/by-warehouse-id/inventory` | Simple: solo `warehouse_id` → inventario + ítems + delivered_* |
| GET | `/api/v2/warehouse/orders/{orderId}` | Orden completa con ítems (formato `OrderListResource`) |
| GET | `/api/v2/warehouse/orders/{orderId}/full` | Orden ampliada (dirección, notas, historial, imágenes) |
| GET | `/api/v2/warehouse/orders/{orderId}/items` | Solo líneas / ítems de la orden |

Alias legado (mismo servicio):

- `POST /api/v2/order/update-delivered-item`
- `POST /api/v2/order/set-status`

---

## Estados usados

| Código | Significado | Endpoint |
|-------:|-------------|----------|
| 9 | Warehouse Assigned | `orders/pending` |
| 11 | Warehouse Completed | `orders/completed` |
| 12 | Completed | `orders/completed` |
| 16 | IN_PROCESS | `update-inventory-stock` |
| — | `tipo_d = I` | `orders/inventory` |

`pending` y `completed` **excluyen** órdenes de inventario.

Los listados básicos (`pending` / `completed` / `inventory`) usan `OrderListResource` (producto con sku/name/unit).  
Para inventario con **fotos y datos completos del producto** usar `orders/inventory/with-products`.

---

## 0. Crear nota de crédito (warehouse)

Siempre `tipo_d = C` (número `OCN…`).  
**Sin restricción de roles**: solo Bearer + acceso a la compañía.

Hay que enviar **`warehouse_user_id`** (`users.id` del almacenero); se guarda en `orders.warehouse_user_id`. **No hace falta enviar `seller_id`**.

**FIFO:** la creación **no falla** por cantidades insuficientes ni por precio de NC > precio de venta. Se asigna lo que haya disponible y la nota se crea igual.

```bash
curl -s -X POST "http://localhost/api/v2/warehouse/credit-notes" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "company_id": 1,
    "customer_id": 10,
    "warehouse_user_id": 677,
    "discount_type": 1,
    "discount": 0,
    "proforma": false,
    "source": "warehouse",
    "products": [
      { "pro_id": 456, "qty": 2, "price": 10.5, "fixed_price": false, "comment": "" }
    ]
  }' | jq .
```

| Campo | Notas |
|-------|--------|
| `warehouse_user_id` | **Obligatorio**. `users.id` del almacenero → `orders.warehouse_user_id` |
| `seller_id` | **Opcional**. Si se omite: vendedor del cliente → `companies.default_seller_id` → `null` |
| `token_create` / `user_id_token` / `rol_token` | **No se envían**; se rellenan desde el Bearer |
| `discount_type` | `1` = %, `2` = $ |
| `products[]` | Mín. 1; `pro_id`, `qty>0`, `price>=0`, `fixed_price` |

Respuesta **201**: `data` con la nota (OrderListResource), incluyendo `warehouse_user_id`, `warehouse_user` y `warehouse_user_display`.

### 0.1 Listar notas de crédito

```bash
curl -s "http://localhost/api/v2/warehouse/credit-notes/list?company_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | jq .
```

Filtros opcionales: `warehouse_user_id`, `customer_id`, `page`, `per_page`, fechas (`date_from` / `date_to` según listados warehouse).  
Cada fila incluye el usuario warehouse que creó la NC.

---

## 1. Cambiar estado de orden (`setstatus_post`)

```bash
curl -s -X POST "http://localhost/api/v2/warehouse/set-status" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "company_id": 1,
    "order_id": 123,
    "status": 9
  }' | jq .
```

**Comportamiento (igual al legado):**

- Body JSON o form: `order_id` + `status` (`cod_status`)
- Si `status == 10` → se guarda como **9** (Warehouse Assigned)
- Actualiza `orders.status_id` (y texto `order_status` vía modelo)
- Inserta registro en `order_status_history`

**Respuesta 200 (ejemplo):**

```json
{
  "status": "1",
  "success": true,
  "message": "Estado actualizado correctamente",
  "order_id": 123,
  "status_id": 15,
  "cod_status": 9,
  "list": { "order_status": 9 },
  "where": "order_id=123",
  "update": true
}
```

---

## 2. Actualizar ítem entregado

```bash
curl -s -X POST "http://localhost/api/v2/warehouse/update-delivered-item" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "company_id": 1,
    "order_id": 123,
    "product_id": 456,
    "delivered_qty": 10,
    "delivered_pack": 2
  }' | jq .
```

**Efecto:**

1. Actualiza `order_details.delivered_quantity` / `delivered_pack`
2. Elimina registros previos en `delivered_orders` (antes `order_dispached`)
3. Inserta el nuevo registro en `delivered_orders`

---

## 2b. Despachar inventario (`updateInventoryStatus_post`)

```bash
curl -s -X POST "http://localhost/api/v2/warehouse/update-inventory-status" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "company_id": 1,
    "order_id": 123,
    "order_status": 3,
    "delivery_status": 1,
    "productlist": [
      {
        "product_id": 456,
        "delivered_quantity": 10,
        "delivered_pack": 2
      }
    ]
  }' | jq .
```

**Comportamiento (igual al legado):**

- Requiere `order_id`, `order_status` (`cod_status`), `delivery_status`
- Si `delivery_status == 0` → se fuerza `order_status = 0`
- Actualiza `orders.delivery_status` y `orders.status_id`
- Inserta historial en `order_status_history`
- Opcional `productlist[]`: por cada ítem actualiza `order_details` (`qty`, `pack`, `delivered_*`), borra e inserta en `delivered_orders`

**Respuesta 200 (ejemplo):**

```json
{
  "status": "1",
  "success": true,
  "message": "Orden despachada correctamente.",
  "order_id": 123,
  "order_status": 3,
  "delivery_status": 1,
  "status_id": 5,
  "items_updated": 1
}
```

---

## 2c. Actualizar stock de línea (`updateInventoryStock_post`)

```bash
curl -s -X POST "http://localhost/api/v2/warehouse/update-inventory-stock" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "company_id": 1,
    "order_id": 123,
    "product_id": 456,
    "warehouse_user_id": 7,
    "stock_quantity": 12
  }' | jq .
```

**Comportamiento (igual al legado):**

- Requiere `order_id`, `product_id`, `warehouse_user_id`, `stock_quantity`
- Actualiza `order_details.qty` de esa línea
- Fija la orden en `cod_status` **16** (`IN_PROCESS`)
- Inserta historial en `order_status_history`
- `warehouse_user_id` es obligatorio por contrato (compatibilidad); no altera `products.stock`

**Respuesta 200 (ejemplo):**

```json
{
  "status": "1",
  "success": true,
  "message": "Stock actualizado correctamente.",
  "order_id": 123,
  "product_id": 456,
  "stock_quantity": 12,
  "stock": 100,
  "cod_status": 16,
  "status_id": 22,
  "detail_id": 789
}
```

---

## 3. Órdenes pendientes

```bash
curl -s -X GET "http://localhost/api/v2/warehouse/orders/pending?company_id=1&page=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | jq .
```

---

## 4. Órdenes terminadas

```bash
curl -s -X GET "http://localhost/api/v2/warehouse/orders/completed?company_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | jq .
```

---

## 5. Órdenes de inventario

```bash
curl -s -X POST "http://localhost/api/v2/warehouse/orders/inventory" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "desde_fecha": "2026-01-01",
    "hasta_fecha": "2026-09-17",
    "estado": 2,
    "page": 1,
    "per_page": 25
  }' | jq .
```

---

## 5b. Inventario con productos completos + fotos

```bash
curl -s -X GET "http://localhost/api/v2/warehouse/orders/inventory/with-products?company_id=1&page=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | jq .
```

Cada `order_details[].product` incluye el producto completo (`toArray`), más:

- `unit`
- `images` / `default_image`
- `categories`, `category_group`, `category_subgroup`
- stock, precios, sku, descriptions, etc.

---

## 5c. Assigned + Completed con info completa

Nuevo endpoint (no modifica `orders/pending` ni `orders/completed`).

```bash
curl -s -X GET "http://localhost/api/v2/warehouse/orders/assigned-and-completed?company_id=1&page=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | jq .
```

Opcional: `group=assigned` | `group=completed` | `group=all` (default).

**Respuesta:**

```json
{
  "success": true,
  "data": {
    "warehouse_assigned": [ /* cod_status 9 */ ],
    "completed": [ /* cod_status 11 y 12 */ ]
  },
  "meta": {
    "warehouse_assigned": { "current_page": 1, "per_page": 25, "total": 3, "last_page": 1, "status_codes": [9] },
    "completed": { "current_page": 1, "per_page": 25, "total": 10, "last_page": 1, "status_codes": [11, 12] }
  }
}
```

Cada orden trae customer, seller, warehouse, address, notes, productos (completos + fotos) y en cada ítem:

- `delivered_quantity` / `delivered_qty`
- `delivered_pack`

Excluye inventario (`tipo_d = I`).

---

## 5d. Listados simples solo con `warehouse_id`

Mismo formato que `GET /api/v2/order/list` (`OrderListResource`: orden + customer + seller + status + `order_details` con producto sku/name/unit).  
Siempre incluyen en cada ítem: `delivered_quantity`, `delivered_qty`, `delivered_pack`.

```bash
# Asignadas (cod_status 9)
curl -s "http://localhost/api/v2/warehouse/by-warehouse-id/assigned?warehouse_id=28" \
  -H "Authorization: Bearer YOUR_TOKEN" | jq .

# Completadas (11, 12)
curl -s -X POST "http://localhost/api/v2/warehouse/by-warehouse-id/completed" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"warehouse_id":28}' | jq .

# Inventario (tipo_d=I)
curl -s "http://localhost/api/v2/warehouse/by-warehouse-id/inventory?warehouse_id=28" \
  -H "Authorization: Bearer YOUR_TOKEN" | jq .
```

`warehouse_id` = user_id del almacenero (como en assign-warehouse / `order/warehouse-user/orders`).

---

## 6. Leer una orden completa (con ítems)

Mismo formato que el listado original de órdenes.

```bash
curl -s -X GET "http://localhost/api/v2/warehouse/orders/123?company_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | jq .
```

**Respuesta 200 (estructura):**

```json
{
  "success": true,
  "message": "Orden de almacén",
  "data": {
    "order_id": 123,
    "customer": { "...": "..." },
    "seller": { "...": "..." },
    "warehouse": { "name": "..." },
    "order_status": 9,
    "status_name": "...",
    "order_details": [
      {
        "detail_id": 1,
        "product_id": 456,
        "quantity": 10,
        "delivered_quantity": 8,
        "product": {
          "pro_id": 456,
          "sku": "ABC-1",
          "name": "Producto",
          "unit": { "unit_id": 1, "unit": "UND" }
        }
      }
    ]
  }
}
```

---

## 7. Orden ampliada (`/full`)

Incluye dirección, compañía, notas, historial de estados e imágenes de producto.

```bash
curl -s -X GET "http://localhost/api/v2/warehouse/orders/123/full?company_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | jq .
```

---

## 8. Solo ítems

```bash
curl -s -X GET "http://localhost/api/v2/warehouse/orders/123/items?company_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | jq .
```

Filtro opcional: `product_id`.

---

## Filtros comunes

| Campo | Uso |
|-------|-----|
| `company_id` | Compañía (si no se envía, se toma del token) |
| `warehouse_user_id` / `warehouse_user` / `user_id` | Filtrar por almacenero |
| `customer_id` | Cliente (pending/completed) |
| `estado` / `status` | `cod_status` (inventory) |
| `desde_fecha` / `hasta_fecha` | Rango de fechas (inventory) |
| `include_order_details` | `0` para omitir ítems en listados (por defecto incluidos) |
| `page` / `per_page` | Paginación (máx. 200) |

---

## Permisos

Usuario autenticado con acceso a la compañía (`canAccessCompany`).

| Endpoint | Roles |
|----------|--------|
| `credit-notes` / `credit-notes/list` | **Sin** restricción de roles. Create exige `warehouse_user_id` del body |
| listados / show / items | Acceso a compañía |
| `update-delivered-item`, `set-status`, `update-inventory-status`, `update-inventory-stock` | admin, manager, seller o warehouse de esa compañía |

---

## Regenerar Swagger

Desde el contenedor / carpeta `api`:

```bash
php artisan l5-swagger:generate
```

Archivo generado: `storage/api-docs/api-docs.json`.
