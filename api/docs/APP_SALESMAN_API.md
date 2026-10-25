# APIs AppSalesman — `/api/v2/app-salesman`

Documentación de la sección **AppSalesman** (Swagger tag: `AppSalesman`).

UI Swagger: `/api/v2/documentation` (también `/swagger` y `/api-docs`).

## Autenticación

**Pública: sin token.** No usa Sanctum ni `X-Api-Key`. Solo rate limit (`throttle:120,1`).

---

## Endpoints

| Método | Path | Descripción |
|--------|------|-------------|
| GET | `/api/v2/app-salesman/ping` | Comprueba que la API responde |
| POST | `/api/v2/app-salesman/orderList` | Listado de órdenes del cliente (réplica legado `orderList_post`) |
| POST | `/api/v2/app-salesman/orderListCompany` | Listado de órdenes de la compañía del mes en curso |
| POST | `/api/v2/app-salesman/orderListSeller` | Órdenes del vendedor logueado (`seller_id`) del mes en curso |
| POST | `/api/v2/app-salesman/requested` | Cantidades solicitadas por producto/cliente (`SUM(qty)`, `tipo_d=D`) |
| POST | `/api/v2/app-salesman/productFifo` | Capas FIFO detalladas de la compañía (por orden; para NC) |
| POST | `/api/v2/app-salesman/productFifoSummary` | Resumen FIFO agregado por `customer_id` + `sku` |
| POST | `/api/v2/app-salesman/catalogLink` | Genera link de catálogo (token + fecha) para un cliente |

---

## Ping

```bash
curl -s -X GET "http://localhost/api/v2/app-salesman/ping" \
  -H "Accept: application/json" | jq .
```

**Respuesta 200:**

```json
{
  "status": "1",
  "success": true,
  "message": "API AppSalesman operativa."
}
```

---

## Order list (`orderList`)

Réplica del endpoint legado REST CodeIgniter `orderList_post`.

- Filtra la vista `warehouseorders` con `customer_id` + `tipo_d = 'D'`, orden `created DESC`.
- Incluye `product_list` por orden (detalle + producto).
- Totales calculados como en el legado (`qty * sale_price`, menos `discount` de la orden).
- Respuestas de negocio/validación con **HTTP 200** y `status: "0"` (igual que el legado).

Si `warehouseorders` está como stub de NULLs, recrearla desde `api/database/sql/views/warehouseorders.sql` (ver `api/docs/WAREHOUSEORDERS_VIEW.md`).

### Request

```bash
curl -s -X POST "http://localhost/api/v2/app-salesman/orderList" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"language_id":2,"user_id":15,"customer_id":608}' | jq .
```

| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|--------|
| `language_id` | int | sí | Compatibilidad con app legado |
| `user_id` | int | sí | Compatibilidad (no filtra el listado) |
| `customer_id` | int | sí | Cliente cuyas órdenes se listan |

### Respuesta 200 — éxito

```json
{
  "status": "1",
  "language_id": 2,
  "user_id": 1,
  "order_list": [
    {
      "tipo_d": "D",
      "order_id": 12345,
      "order_number": "ORD-001",
      "order_comments": "",
      "ordered_total": "100.00",
      "discount": "0.00",
      "discount_a": "0.00",
      "amount": "100.00",
      "total_amount": "100.00",
      "discount_type": 2,
      "business_name": "Acme SA",
      "customer_id": 608,
      "name": "Cliente",
      "email": "cliente@example.com",
      "phone": "5551234",
      "created": "2026-03-20 10:00:00",
      "updated": "2026-03-20 10:00:00",
      "name_status_spanish": "Pendiente",
      "name_status_english": "Pending",
      "scolor": "#ff0000",
      "warehouse_user_id": "",
      "warehouse_assign_date": "",
      "warehouse_name": "",
      "product_list": [
        {
          "order_id": 12345,
          "currency_type": "$",
          "product_id": 5098,
          "name": "PRODUCT NAME",
          "sku": "SKU-1",
          "qty": "2.00",
          "sale_price": "50.00",
          "fob_price": "0.00",
          "purchase_price": "0.00",
          "barcode": "",
          "discount": "0.00",
          "discount_type": 2,
          "comment": "",
          "created": "2026-03-20 10:00:00"
        }
      ]
    }
  ]
}
```

Notas de compatibilidad:

- `user_id` en la respuesta = `seller_id` de la última orden (equivalente al `user_id` de la vista legacy `warehouseorders`).
- `scolor` = color del estado (`status_orders.color`).
- Importes y cantidades van como string con `number_format(..., 2)` (coma de miles si aplica).
- Nulls se convierten a `""`.

### Respuesta 200 — sin órdenes

```json
{
  "status": "0",
  "error": "No Order found."
}
```

### Respuesta 200 — validación

```json
{
  "status": "0",
  "error": "The Customer Id field is required."
}
```

---

## Order list company (`orderListCompany`)

Réplica del endpoint legado REST CodeIgniter `orderListCompany_post`, acotada al **mes en curso**.

- Filtra la vista `warehouseorders` con `company_id` + `tipo_d = 'D'` + `created` entre el primer y el último día del mes actual, orden `created DESC`.
- Mismo formato de respuesta que `orderList` (incluye `product_list` por orden).
- Compatibilidad: si no viene `company_id` pero sí `customer_id` (form legado), se usa como `company_id`.
- Respuestas de negocio/validación con **HTTP 200** y `status: "0"`.

### Request

```bash
curl -s -X POST "http://localhost/api/v2/app-salesman/orderListCompany" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"language_id":2,"user_id":15,"company_id":1}' | jq .
```

| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|--------|
| `language_id` | int | no | Compatibilidad con app legado |
| `user_id` | int | sí | Compatibilidad (no filtra el listado) |
| `company_id` | int | sí | Compañía cuyas órdenes se listan |

### Respuesta 200 — éxito

Misma estructura que `orderList` (`status`, `language_id`, `user_id`, `order_list`).

### Respuesta 200 — sin órdenes

```json
{
  "status": "0",
  "error": "No Order found."
}
```

### Respuesta 200 — validación

```json
{
  "status": "0",
  "error": "The Company Id field is required."
}
```

---

## Order list seller (`orderListSeller`)

Órdenes **solo del vendedor logueado**, mes en curso.

- Resuelve `sellers.id` con `sellers.user_id = user_id` + `company_id` (o acepta `seller_id` directo).
- Filtra `orders.seller_id` (en vista `warehouseorders` = columna `user_id`) + `tipo_d = 'D'` + `created` del mes actual.
- Mismo formato de respuesta que `orderListCompany` (+ `seller_id` en éxito).

### Request

```bash
curl -s -X POST "http://localhost/mvc/api/v2/app-salesman/orderListSeller" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"language_id":2,"user_id":15,"company_id":1}' | jq .
```

| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|--------|
| `language_id` | int | no | Compatibilidad |
| `user_id` | int | sí | `users.id` del vendedor logueado |
| `company_id` | int | sí | Compañía |
| `seller_id` | int | no | Si se envía, se usa directo (sin resolver por user) |

---

## Product FIFO (`productFifo`)

Listado **detallado** de capas FIFO de toda la compañía (una fila por orden/línea), para armar notas de crédito.

**Body:** solo `company_id` (opcional `only_available`).

Campos por ítem: `customer_id`, `sku`, `product_name`, `unit`, fechas de orden, `sales_price`, `product_sales_price`, `qty_sales`, `qty_creditnote`, `qty_total`, `order_id`, `order_number`.

- Auth pública (sin token). Capas FIFO ASC por `opened_at`.
- Ventas entran al completar (`WAREHOUSE_COMPLETED` / `COMPLETED`); NC consumen al crearse.
- Backfill: `php artisan fifo:rebuild --company=1`

```bash
curl -s -X POST "http://localhost/api/v2/app-salesman/productFifo" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1}' | jq .
```

---

## Product FIFO Summary (`productFifoSummary`)

**Resumen** agregado por `customer_id` + `sku` (sin órdenes ni fechas).

**Body:** solo `company_id`.

| Campo | Significado |
|-------|-------------|
| `customer_id` | Cliente |
| `sku` | SKU |
| `product_name` | Nombre del producto |
| `unit` | Unidad de medida |
| `qty_sales` | Suma vendida |
| `qty_credit_notes` | Suma acreditada (NC) |
| `qty_total` | Disponible (`qty_sales - qty_credit_notes`) |

```bash
curl -s -X POST "http://localhost/api/v2/app-salesman/productFifoSummary" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1}' | jq .
```

### Respuesta 200 — éxito

```json
{
  "status": "1",
  "company_id": 1,
  "items": [
    {
      "customer_id": 608,
      "sku": "SKU-1",
      "product_name": "PRODUCTO EJEMPLO",
      "unit": "UND",
      "qty_sales": 20,
      "qty_credit_notes": 5,
      "qty_total": 15
    }
  ]
}
```

---

## Requested (`requested`)

Cantidades solicitadas agregadas por producto y cliente para una compañía.

Consulta equivalente:

```sql
SELECT
  b.company_id,
  b.customer_id,
  a.product_id,
  c.sku,
  SUM(ABS(a.qty)) AS qty
FROM order_details a
INNER JOIN orders b ON b.order_id = a.order_id
INNER JOIN products c ON c.pro_id = a.product_id
WHERE b.tipo_d = 'D'
  AND b.company_id = ?
GROUP BY b.company_id, a.product_id, b.customer_id, c.sku
```

- Requiere `company_id`.
- Solo órdenes `tipo_d = 'D'`.
- `qty` usa `ABS` (cantidades negativas de documento no restan).
- Respuestas de negocio/validación con **HTTP 200** y `status: "0"`.

### Request

```bash
curl -s -X POST "http://localhost/api/v2/app-salesman/requested" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1}' | jq .
```

| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|--------|
| `company_id` | int | sí | Compañía a filtrar |

### Respuesta 200 — éxito

```json
{
  "status": "1",
  "company_id": 1,
  "requested": [
    {
      "company_id": 1,
      "customer_id": 608,
      "product_id": 5098,
      "sku": "SKU-1",
      "qty": 12.5
    }
  ]
}
```

### Respuesta 200 — sin datos

```json
{
  "status": "0",
  "error": "No requested found."
}
```

### Respuesta 200 — validación

```json
{
  "status": "0",
  "error": "The Company Id field is required."
}
```

---



---

## Catalog link (`catalogLink`)

Genera un enlace público de catálogo para un cliente, con el mismo mecanismo que **enviar catálogo** (`CatalogTokenService` + `catalog_cart`), **sin enviar** email/SMS/WhatsApp.

- Requiere `company_id` y `customer_id` (el cliente debe pertenecer a la compañía).
- `catalog_id` opcional; si no se envía, usa el catálogo `default_catalog` o el primero activo de la compañía.
- Devuelve `link`, `token`, `expiration_date` y `created`.
- URL: `{FULLVENDOR_WEB_URL}/catalogo/producto?token=...`

### Request

```bash
curl -s -X POST "http://localhost/api/v2/app-salesman/catalogLink" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1,"customer_id":608}' | jq .
```

| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|--------|
| `company_id` | int | sí | Compañía |
| `customer_id` | int | sí | Cliente de esa compañía |
| `catalog_id` | int | no | Catálogo concreto; si falta, se resuelve automáticamente |

### Respuesta 200 — éxito

```json
{
  "status": "1",
  "company_id": 1,
  "customer_id": 608,
  "catalog_id": 12,
  "link": "http://localhost/catalogo/producto?token=eyJpdiI6...",
  "token": "eyJpdiI6...",
  "expiration_date": "2026-04-10T12:00:00+00:00",
  "created": "2026-03-26T12:00:00+00:00"
}
```

### Respuesta 200 — validación / error

```json
{
  "status": "0",
  "error": "The Customer Id field is required."
}
```


## Swagger

Tras añadir o cambiar endpoints, el agente debe ejecutar **siempre** (sin pedirlo):

```bash
php artisan openapi:sync --scan=AppSalesman
```

UI: `/api/v2/documentation` — tag **AppSalesman** (sin security scheme; APIs públicas).

Endpoints:

- `GET api/v2/app-salesman/ping`
- `POST api/v2/app-salesman/orderList`
- `POST api/v2/app-salesman/orderListCompany`
- `POST api/v2/app-salesman/requested`
- `POST api/v2/app-salesman/productFifo`
- `POST api/v2/app-salesman/productFifoSummary`
- `POST api/v2/app-salesman/catalogLink`
