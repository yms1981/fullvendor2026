# APIs ERP — `/api/v2/erp`

Documentación de la sección **Erp** (Swagger tag: `Erp`).

UI Swagger: `/api/v2/documentation` (también `/swagger` y `/api-docs`).

## Autenticación

No usa Sanctum. Todas las rutas bajo `/api/v2/erp` requieren el header:

```http
X-Api-Key: f008aa014308059be6c1c20ed70a006e
```

El valor se configura con `ERP_API_KEY` en `.env` (ver `config/erp.php`).

Sin la clave correcta → **401**.

Casi todos los endpoints de datos requieren `company_id` (query, body o header `X-Company-Id`).

---

## Endpoints

| Método | Path | Descripción |
|--------|------|-------------|
| GET | `/api/v2/erp/ping` | Comprueba que la API key es válida |
| GET | `/api/v2/erp/orders` | Listar órdenes (shape softorderList) |
| GET | `/api/v2/erp/orders/{orderNumber}` | Detalle de orden por número |
| POST | `/api/v2/erp/orders/import` | Importar/actualizar orden (autenticado) |
| GET | `/api/v2/erp/products` | Listar productos |
| GET | `/api/v2/erp/products/without-images` | Productos sin fotos (para que el ERP las complete) |
| POST | `/api/v2/erp/products/upsert` | Crear/actualizar producto por SKU |
| POST | `/api/v2/erp/products/stock-update` | Actualizar stock/precios por lote |
| POST | `/api/v2/erp/products/images` | Subir imágenes (multipart) y asignar al producto |
| GET | `/api/v2/erp/customers` | Listar clientes |
| POST | `/api/v2/erp/customers/upsert` | Crear/actualizar cliente (incluye `balance`) |
| GET | `/api/v2/erp/categories` | Listar categorías |
| GET | `/api/v2/erp/customer-groups` | Listar grupos de clientes |
| GET | `/api/v2/erp/terms` | Listar términos de venta |
| GET | `/api/v2/erp/sellers` | Listar vendedores |
| GET | `/api/v2/erp/warehouse-users` | Listar usuarios de almacén |

---

## Ping

```bash
curl -s -X GET "http://localhost/api/v2/erp/ping" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e" \
  -H "Accept: application/json" | jq .
```

**Respuesta 200:**

```json
{
  "status": "1",
  "success": true,
  "message": "API ERP operativa."
}
```

---

## Órdenes

### Listar

```bash
curl -s "http://localhost/api/v2/erp/orders?company_id=1&limit=50" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
```

Query opcionales: `tipo_d`, `from`, `to`, `limit` (1–500).

### Detalle

```bash
curl -s "http://localhost/api/v2/erp/orders/SO-1001?company_id=1" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
```

### Importar

```bash
curl -s -X POST "http://localhost/api/v2/erp/orders/import" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e" \
  -H "Content-Type: application/json" \
  -d @order-payload.json
```

Misma lógica que el import público `/order/import`, pero autenticado con `X-Api-Key`.

---

## Productos

### Listar

```bash
curl -s "http://localhost/api/v2/erp/products?company_id=1" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
```

### Sin fotos

Devuelve productos sin `default_image` usable ni `product_images` con imagen en storage activo.

```bash
curl -s "http://localhost/api/v2/erp/products/without-images?company_id=1&limit=500" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
```

Cada ítem: `pro_id`, `sku`, `name`, `has_image: false`. El ERP usa este listado para subir fotos locales vía `POST /products/images`.

### Upsert por SKU

```bash
curl -s -X POST "http://localhost/api/v2/erp/products/upsert" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1,"sku":"ABC-001","name":"Producto","sale_price":10,"stock":5}'
```

### Stock / precios por lote

```bash
curl -s -X POST "http://localhost/api/v2/erp/products/stock-update" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1,"items":[{"sku":"ABC-001","stock":12,"fob_price":4.5,"purchase_price":5,"sale_price":10}]}'
```

También acepta un objeto único con `sku` (sin `items[]`).

### Subir imágenes

`multipart/form-data`. Identifica el producto con `pro_id` **o** `sku`.

| Campo | Tipo | Notas |
|-------|------|--------|
| `company_id` | int | obligatorio |
| `pro_id` / `sku` | int / string | uno de los dos |
| `images[]` | file(s) | también `image`, `files`, `contents` |
| `default_index` | int | opcional, **0-based** entre las subidas de este request |
| `replace` | bool | si `1`/`true`, reemplaza la galería actual |

**Política de guardado (igual que el resto de FullVendor)**

1. Disco según `IMAGES_DISK` (`minio`/`s3` o `images_local`) vía `Image::putContent`.
2. Normalización obligatoria: JPEG (calidad ~82), máx. ~1200×1200 (`config/images.php`), `type=product`.
3. Path físico:
   - object storage → `{company_id}/{name}`
   - local → `products/{company_id}/{name}`
4. Nombre de archivo / display (slots `img_order`):
   - slot **1** → `{SKU}.jpg` (imagen principal)
   - slot **N** → `{SKU}_PIC{N}.jpg`
5. Máximo **5** imágenes; `img_order` secuencial.
6. `products.default_image_id` = UUID del slot 1 (o la del `default_index`, que se coloca como slot 1).
7. `products.imgURL` / `simgURL` = URL pública `/api/v2/images/{uuid}`.

**Imagen por defecto**

1. Si envías `default_index` válido → esa foto se coloca como **slot 1** (principal).
2. Si no: la primera del batch es slot 1 cuando `replace=1` **o** el producto no tenía default.
3. Append con default ya existente → se conserva (las nuevas van a slots siguientes: `_PIC2`, …).
4. Excedentes sobre 5 → `skipped`.

```bash
curl -s -X POST "http://localhost/api/v2/erp/products/images" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e" \
  -F "company_id=1" \
  -F "sku=ABC-001" \
  -F "default_index=0" \
  -F "replace=0" \
  -F "images[]=@./foto1.jpg" \
  -F "images[]=@./foto2.jpg"
```

Respuesta: `default_image_id`, `default_rule`, `storage_policy`, `uploaded[]` (`uuid`, `name`, `path`, `disk`, `img_order`, `is_default`, `url`).

---

## Clientes

### Listar

```bash
curl -s "http://localhost/api/v2/erp/customers?company_id=1" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
```

### Upsert

Busca por `customer_id` o `cust_id_kor` / `iderp`.

```bash
curl -s -X POST "http://localhost/api/v2/erp/customers/upsert" \
  -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1,"cust_id_kor":"110392","business_name":"ACME","email":"a@acme.com","balance":1250.50}'
```

---

## Catálogos

Todos requieren `company_id`:

```bash
curl -s "http://localhost/api/v2/erp/categories?company_id=1" -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
curl -s "http://localhost/api/v2/erp/customer-groups?company_id=1" -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
curl -s "http://localhost/api/v2/erp/terms?company_id=1" -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
curl -s "http://localhost/api/v2/erp/sellers?company_id=1" -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
curl -s "http://localhost/api/v2/erp/warehouse-users?company_id=1" -H "X-Api-Key: f008aa014308059be6c1c20ed70a006e"
```

---

## Regenerar Swagger

Tras cambiar anotaciones OpenAPI en los controladores Erp:

```bash
cd mvc/api
php artisan openapi:sync --scan=Erp --skip-full
```

(`l5-swagger:generate` completo a menudo falla por conflictos LSPH en otros controllers; `openapi:sync` fusiona solo el módulo Erp.)

Luego recarga `/api/v2/documentation` (hard refresh).
