# Flujo de creación de orden (company)

Documento de referencia del flujo **canónico** en `/company/orders`.  
Los módulos paralelos `order13` / `orders13` y las vistas legacy de listado (`new_order_list`, etc.) fueron **eliminados**; el controlador legacy `company/Order.php` aún convive hasta completar su retirada.

---

## 1. Entrada al flujo

| Paso | Dónde | Qué pasa |
|------|--------|----------|
| 1.1 | **Listado órdenes** | `company/orders` → `orders/Home` → listado IsCRUD (partials en `views/company/order/list/*`, filtro `form_filter.php`). |
| 1.2 | **Botón "Agregar nueva orden"** | Abre modal `#modal_catalogorder` → vista `company/order/modal_addneworder.php`. |
| 1.3 | **Formulario del modal** | POST AJAX a `company/orders/generateCatalogOrder` → `orders/GenerateCatalogOrder.php`. |
| 1.4 | **Búsqueda de cliente en modal** | GET vía `orders/searchCustomer` (API `company/{id}/customers`). |

---

## 2. Opciones en el modal (`generateCatalogOrder`)

Tras validar cliente y grupo (`customergroup`), según el radio elegido:

### 2.1 Desde catálogo (`type_catalog`)

1. **Preparar catálogo** (si hace falta): `POST api/v2/company/{id}/prepare-order-catalog`  
   - Categoría por defecto + catálogo FullVendor (`FullVendorCatalogService`).
2. **Crear sesión de carrito**: `POST api/v2/company/{id}/order-catalog-session`  
   - Inserta fila en tabla legacy **`catalog_cart`** con token cifrado (`OrderCatalogCartSessionService`).
3. **Redirect** en nueva ventana: `catalogo/producto?token={token_kor}`.

El usuario agrega productos en el catálogo público (`catalogo/Producto.php`). El MVC consume la API pública **`catalog-public/*`** autenticada por `?token=` / `X-Catalog-Token` (sin Bearer). Los datos viven en `catalog_cart` + `detail_car`; la lógica de negocio está en la API.

Al confirmar (**place_order**):

1. MVC valida token, dirección y líneas del carrito (UI).
2. `POST api/v2/catalog-public/place-order?token={token_kor}` → `CatalogPublicPlaceOrderService` (transacción atómica: orden + `order_details`, cierre de `catalog_cart`, limpieza de `detail_car` si aplica).
3. La API envía emails post-orden (`CatalogPublicOrderEmailService` + `PlaceOrderMail`) según `type_cart` y flags de la empresa.
4. MVC solo aplica flash + redirect (`finishCatalogPlaceOrderSuccess`); **no** usa `createOrderLocal`, `POST api/v2/order` ni SQL directo en el controlador.

**Retirado (C12–C13):** `createOrderViaApi`, `createOrderLocal`, fallback `OrdenModel::makeOrder` / `saveOrder`, emails vía `EmailOrderModel` en MVC.

### 2.2 Desde grilla (`type_grid`)

Redirect a:

```
company/orders/add?customer={id}&tipo_d=D|C
```

(`tipo_d`: `D` = orden, `C` = nota de crédito).

### 2.3 Desde archivo CSV (`type_file`)

Parsea CSV (`;`, 4 columnas: sku, nombre, qty, precio) y redirect a:

```
company/orders/add?customer={id}&tipo_d=...&detail={json líneas}
```

---

## 3. Pantalla “Alta de orden” (formulario manual)

| Paso | Dónde | Qué pasa |
|------|--------|----------|
| 3.1 | **URL** | `company/orders/add?customer=123` (opcional `tipo_d`, `detail`, `duplicated`). |
| 3.2 | **Controlador** | `orders/Add.php` → `run()`. |
| 3.3 | **Datos** | Cliente, vendedores y formatos vía API. Líneas CSV/duplicado desde query. |
| 3.4 | **Vista** | `company/order/order_add.php`. |
| 3.5 | **Riesgo** | Si el cliente no tiene vendedores, la vista puede fallar al usar `$customer->sellers[0]`. |

---

## 4. Envío del formulario (Guardar)

| Paso | Dónde | Qué pasa |
|------|--------|----------|
| 4.1 | **Botón** | Clase `btn-update`; el click lo maneja JS (no submit nativo del form). |
| 4.2 | **JS** | `companyasset/company/order/index.js`: lee cliente, vendedor, descuento, proforma, comentarios y filas `.table tbody tr.item`. |
| 4.3 | **Payload** | `{ customer_id, seller_id, discount_type, discount, proforma, internal_comments, products, tipo_d }`. |
| 4.4 | **AJAX** | POST a `options.url + '/orders/save'` con body `{ data: JSON.stringify(payload) }`. |
| 4.5 | **options.url** | Definido en `order_add.php` desde `company_url()` si no existía. |

---

## 5. MVC – Save

| Paso | Dónde | Qué pasa |
|------|--------|----------|
| 5.1 | **Controlador** | `orders/Save.php` → `run()`. |
| 5.2 | **Entrada** | JSON en `post('data')` o `buildPayloadFromForm()` si es submit clásico. |
| 5.3 | **API** | `$this->post('order', $data)`; `Company_Controller` inyecta **`company_id`**. |
| 5.4 | **Éxito** | Redirect o JSON; tras crear → `company/orders/view/{order_id}`. |
| 5.5 | **Error** | `hasError($response)` → `jsonError()`. |

---

## 6. API – Crear orden

| Paso | Dónde | Qué pasa |
|------|--------|----------|
| 6.1 | **Ruta** | POST `api/v2/order` → `OrderController::store()`. |
| 6.2 | **Request** | `StoreOrderRequest`: `customer_id`, `seller_id`, `discount_type`, `discount`, `proforma`, `internal_comments`, `products`, etc. |
| 6.3 | **Autorización** | Policy `create` sobre `Order`. |
| 6.4 | **company_id** | Desde usuario autenticado / compañía activa. |
| 6.5 | **seller_id** | Si viene `0`, se resuelve vendedor por token; si no hay → 422. |
| 6.6 | **Transacción** | `order_number`, `Order` + `OrderDetail` por cada `pro_id` en `products`. |
| 6.7 | **Errores** | Producto inexistente, idioma, límites de plan → 4xx/5xx con rollback. |

Endpoints auxiliares del flujo catálogo (company, **con Bearer**):

| Endpoint | Servicio / controlador |
|----------|-------------------------|
| `POST company/{id}/prepare-order-catalog` | `CompanyController::prepareOrderCatalog` |
| `POST company/{id}/order-catalog-session` | `CompanyController::createOrderCatalogSession` |

API pública del catálogo (**sin Bearer**, auth por `token_kor`):

| Endpoint | Uso |
|----------|-----|
| `GET catalog-public/session` | Sesión carrito, company, **catalog**, **seller**, customer, formato (`?touch=1` marca vista) |
| `GET catalog-public/totals` | Totales formateados del carrito |
| `GET catalog-public/lines` | Líneas `detail_car` |
| `PATCH catalog-public/session` | `view`, `status_cart`, expirar carrito |
| `GET catalog-public/products` | Listado productos (filtros: `cat_id`, `search`, `short`, paginación) |
| `GET catalog-public/products/{productId}` | Detalle producto para modal (precio cliente, imágenes, línea carrito) |
| `GET catalog-public/products/count` | Conteo productos |
| `GET catalog-public/categories-order` | Categorías ordenadas del catálogo |
| `GET catalog-public/categories` | Listado categorías |
| `PUT catalog-public/lines` | Upsert cantidad/comentario (`product_id`, `qty`; `qty=0` elimina) |
| `POST catalog-public/lines/add` | Incrementar cantidad (mínimo de compra) |
| `DELETE catalog-public/lines/{productId}` | Eliminar línea |
| `DELETE catalog-public/lines` | Vaciar carrito |
| `POST catalog-public/place-order` | Checkout atómico + emails |

Middleware: `catalog.cart.token` (`ResolveCatalogCartToken`). Controlador: `CatalogPublicController`. Servicios: `CatalogPublicSessionService`, `CatalogCartTotalsService`, `CatalogPublicCartLinesService`, `CatalogPublicCatalogService`, `CatalogPublicCartMutationService`, `CatalogPublicPlaceOrderService`, `CatalogPublicOrderEmailService`.

Imágenes públicas del catálogo: `GET api/v2/images/catalog/{uuid}.{ext}?token=…` (`ImageController::imageForCatalog`).

---

## 7. Resumen (diagrama)

```
[Listado company/orders]
  → Modal modal_addneworder
  → POST orders/generateCatalogOrder
       ├─ Catálogo → API prepare + order-catalog-session
       │              → catalogo/producto?token=…
       │              → catalog-public/* (sesión, productos, carrito)
       │              → place_order → POST catalog-public/place-order
       │              → flash + redirect (emails en API)
       ├─ Grilla   → redirect orders/add?customer=…
       └─ CSV      → redirect orders/add?customer=…&detail=…

[orders/add] → order_add + index.js
  → POST orders/save → POST api/v2/order (Bearer company)
  → redirect orders/view/{id}
```

---

## 8. Otros consumidores de órdenes (fuera de este doc)

| Módulo | Creación | Listado |
|--------|----------|---------|
| **Portal customer** | Catálogo embed → `catalogo/Producto` → `catalog-public/*` | API `GET customer/{id}/orders` |
| **Portal seller** | `POST api/v2/order` (`source: seller_portal`) | API `GET order/list` |
| **Warehouse** | — | API `GET order/company/{id}/warehouse` |
| **Inventory** | API `POST order/inventory/create` | API inventory |

No usan `company/orders/*` ni el modal de company; comparten la misma tabla `orders` en API.

---

## 9. Legacy

| Pieza | Estado |
|-------|--------|
| `controllers/company/Order.php` | **Shim** (~120 líneas): redirige GET y reenvía POST a `company/orders/*`. |
| `Order.php` monolítico (SQL) | **Retirado** (fase C8). |
| Vistas muertas (`order_list`, `combine_order`, `edit_order`, etc.) | **Eliminadas** (C7). |
| Envío correo | `orders/sendOrder` → API `POST order/sendOrder` (`OrderSendEmailService`). |

**Nota:** `OrdenModel::update_orderbymovil()` ya no se ejecutaba al usar solo `/company/orders`; queda pendiente migrar a API si sigue siendo necesario para órdenes móviles.

---

## 10. Puntos a verificar si hay errores

1. **Tabla `catalog_cart`**  
   Obligatoria para “Desde catálogo” y portal customer. La sesión la crea la API (`order-catalog-session`).

2. **Token `token_kor` en catálogo público**  
   Todas las llamadas `catalog-public/*` requieren `?token=` o header `X-Catalog-Token`. Sin token válido → 404/422. Carrito expirado → 410.

3. **Checkout catálogo (`place-order`)**  
   Ya no depende de Bearer ni de SQL en MVC. Errores frecuentes: carrito vacío, `nosubmit_order`, stock, múltiplos de cantidad mínima, límite de plan (`plan_limit`).

4. **Emails post-orden catálogo**  
   Los envía la API (`CatalogPublicOrderEmailService`) con plantilla `emails.order_details` (Laravel). `to_company` no envía email; `to_portal_seller` y `to_customer` según flags de empresa.

5. **Cliente con vendedor**  
   `generateCatalogOrder` exige seller vía `GET customer/{id}/seller`. En checkout público, la API resuelve `seller_id` desde `catalog_cart.user_id` o `companies.default_seller_id`.

6. **Productos en grilla**  
   Cada `pro_id` debe existir en API; si no, `store()` falla.

7. **company_id en listado**  
   Filtro API vía `OrderFilter::company()` (EloquentFilter).

8. **Límite de plan**  
   Modal respeta `planLimitBlock` (`orders_month`). También aplica en `catalog-public/place-order`.

---

## 11. Archivos clave

| Rol | Ruta |
|-----|------|
| Listado | `mvc/application/controllers/company/orders/Home.php` |
| Modal “Agregar nueva orden” | `mvc/application/views/company/order/modal_addneworder.php` |
| Generar catálogo / grilla / CSV | `mvc/application/controllers/company/orders/GenerateCatalogOrder.php` |
| Búsqueda cliente (modal) | `mvc/application/controllers/company/orders/SearchCustomer.php` |
| Alta manual | `mvc/application/controllers/company/orders/Add.php` |
| Vista formulario | `mvc/application/views/company/order/order_add.php` |
| JS envío | `mvc/companyasset/company/order/index.js` |
| MVC Save | `mvc/application/controllers/company/orders/Save.php` |
| Catálogo público (UI + orquestación) | `mvc/application/controllers/catalogo/Producto.php` |
| Modelo catálogo público (cliente API + fallbacks) | `mvc/application/models/CatalogoPublicModel.php` |
| Middleware token carrito | `api/app/Http/Middleware/ResolveCatalogCartToken.php` |
| API catálogo público | `api/app/Http/Controllers/CatalogPublicController.php` |
| Sesión carrito API (company) | `api/app/Services/OrderCatalogCartSessionService.php` |
| Checkout catálogo público | `api/app/Services/CatalogPublicPlaceOrderService.php` |
| Emails checkout catálogo | `api/app/Services/CatalogPublicOrderEmailService.php` |
| Preparar catálogo API | `api/app/Services/FullVendorCatalogService.php` |
| API store (órden manual / seller) | `api/app/Http/Controllers/OrderController.php` → `store()` |
| API envío correo orden (panel company) | `api/app/Services/OrderSendEmailService.php` → `OrderController::sendOrder()` |
| Validación API orden manual | `api/app/Http/Requests/StoreOrderRequest.php` |

---

## 12. Migración catálogo público (C9–C20)

| Fase | Contenido | Estado |
|------|-----------|--------|
| C9 | `session`, `totals`, `lines`, `patchSession` | Hecho |
| C10 | `products`, `categories`, `products/count` | Hecho |
| C11 | Mutaciones carrito (`lines`, `lines/add`, delete) | Hecho |
| C12 | `place-order` atómico; retirado SQL/Bearer en checkout | Hecho |
| C13 | Emails en API; `Producto.php` sin `$this->db` directo | Hecho |
| C14 | Sesión ampliada (company/catalog/seller/cart); `index`/`carts`/`validar_token` sin SQL de metadatos | Hecho |
| C16 | Mutaciones carrito solo API (`lines*`, `PATCH session`); sin fallback SQL en `saveqty`/`add_qty`/`eliminar`/`vaciarCarrito` | Hecho |
| C15 | Listado catálogo solo API (`products`, `count`, `categories*`); `get_products` autocomplete vía API; imágenes carrito desde `lines` | Hecho |
| C17 | Modal producto vía `GET catalog-public/products/{id}`; sin SQL en `get_product()` | Hecho |
| C18 | Checkout/totals/líneas solo API; `place_order` sin SQL ni `product/{id}` Bearer | Hecho |
| C19 | Historial vía `GET catalog-public/orders` y `GET catalog-public/orders/{id}` | Hecho |
| C20 | Multi-carrito API, `serveImage` sin SQL, limpieza legacy, `GenericCatalog` alineado | Hecho |

El MVC (`Producto.php`) usa `catalog-public/*` de punta a punta. `load->database()` ya no se carga en el constructor del catálogo público; utilidades legacy (`actualizar_imagenes_default`, `tiene_codeRegional`) cargan BD bajo demanda.
