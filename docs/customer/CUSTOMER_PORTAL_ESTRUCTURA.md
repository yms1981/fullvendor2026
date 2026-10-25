# Estructura portal cliente `/customer`

Este documento resume la estructura actual del portal cliente para revisarla posteriormente con Calam.

## Rutas MVC (`mvc/application/config/routes.php`)

- `/customer` -> `customer_portal/index`
- `/customer/login` -> `customer_portal/index` (GET: pantalla login; POST: JSON de login, mismo cuerpo que antes en `authenticate`)
- `/customer/authenticate` -> `customer_portal/authenticate` (**legado**: GET redirige a login; POST = mismo JSON que `customer/login`)
- `/customer/logout` -> `customer_portal/logout`
- `/customer/menu` -> `customer_portal/menu` (**redirige** a `/customer/catalog`; URL legada)
- `/customer/home` -> `customer_portal/catalog` (alias legado → misma pantalla que catálogo)
- `/customer/catalog` -> `customer_portal/catalog`
- `/customer/orders` -> `customer_portal/orders`
- `/customer/orders/new` -> `customer_portal/startOrder`

## Login del portal (flujo completo)

### Entrada y transporte

1. **GET** `/customer` o `/customer/login` → `Customer_portal::index()` → vista `customer/login.php` (formulario + `fetch` en JavaScript).
2. El navegador **no llama a la API Laravel directamente**: hace **POST same-origin** al path `authenticate_path`, que por defecto es el mismo **`/customer/login`** (solo path, sin otro host → evita problemas de CORS si `BASE_URL` y el sitio real difieren en esquema).
3. **POST** a esa URL en el servidor MVC:
   - Si el cuerpo es JSON con `email` / `password` (y opcionalmente `company_id` / `customer_id`), `index()` delega en `portalLoginJson()`.
   - La ruta legada **POST** `/customer/authenticate` hace lo mismo.

### Capa MVC (`Customer_portal::portalLoginJson`)

- Valida en PHP que haya email y contraseña y que el email tenga formato válido; si no, responde JSON `422` con códigos `customer_login_validation_*`.
- Con **Guzzle** llama a **`POST {API_BASE}/v2/customer/login`** con JSON: `email`, `password`, `company_id`, `customer_id` (estos dos últimos opcionales, enviados desde el formulario cuando el usuario ya eligió empresa y cuenta).
- Si la API responde éxito con `data` y el usuario es cliente (`is_customer` / `user_type`):
  - `establishCustomerSession(company_id, customer_id, language_id)` — sesión CodeIgniter y carrito legacy opcional.
  - `syncPortalSanctumToken(email, password)` — **GET** `{API_BASE}/v2/user/token` con query email/password; guarda el Bearer en la sesión (mismo criterio que login company).
  - Respuesta JSON al navegador: `success`, `authenticated`, `redirect` → path de `/customer/catalog`.
- Otros códigos HTTP de la API se reenvían o mapean (p. ej. `502` si el cuerpo no es JSON, `429` si la API devuelve demasiados intentos).

### Capa API (`CustomerPortalLoginController::login`)

- Ruta Laravel: **`POST /api/v2/customer/login`** (prefijo según despliegue; en código: grupo `v2`, middleware **`throttle:30,1`**).
- Valida entrada: `email`, `password` obligatorios; `company_id` y `customer_id` opcionales.
- **Base de datos (Laravel)**:
  - Busca **`users`** por email.
  - Comprueba contraseña con `Hash::check` o `password_verify` (compatibilidad de hashes).
  - Resuelve vínculo cliente mediante pivote **`customer_user`** y modelo **`Customer`** con **`company`** cargada:
    - Solo clientes con `customer_status = 1` y empresa con `status = 1`.
  - Si hay **un solo** candidato → devuelve `company_id`, `customer_id`, `language_id` (prioriza idioma de la empresa si aplica).
  - Si hay **varios** y no vinieron `company_id` + `customer_id` válidos → **`422`** con `code: customer_login_select_company` y listas `companies` / `customers` para el segundo intento desde el navegador.
  - Si no hay pivote o no hay candidatos válidos → **`403`** `customer_login_not_customer`.
- Códigos habituales de error: `customer_login_email_not_found` (404), `customer_login_invalid_password` (401), validación Laravel (422), `customer_login_server_error` (500).

### Vista (`login.php`)

- Traducciones: mapa `errorMap` de códigos API → claves `cmp-customer-portal` (sin textos fijos de negocio en español/inglés en la UI).
- Si la respuesta trae `customer_login_select_company`, muestra el panel de selección de empresa y cuenta y reenvía el POST con `company_id` y `customer_id`.
- Tras login correcto: `window.location.replace` hacia `redirect` del JSON o `menu_path` (valor: path de catálogo).

### Resumen API vs BD en el login

| Paso | ¿Dónde? | Mecanismo |
|------|---------|-----------|
| Validar usuario y cliente | API Laravel | Consultas a `users`, `customer_user`, `customers`, `companies`. |
| Proxy y sesión PHP | MVC | HTTP a la API; luego sesión CI + token en sesión. |
| Pantalla | Vista + JS | `fetch` al MVC, no a Laravel desde el browser (salvo que en otro entorno se cambie `authenticate_path`). |

## Pestaña Órdenes (`GET /customer/orders`)

### Ruta y controlador

- **Ruta:** `customer/orders` → `Customer_portal::orders()`.
- **Sesión:** `requireCustomerSession()`; si no hay sesión de portal, redirige al login.
- **Contexto:** el listado lo resuelve la API para el `customer_id` de sesión (incluye **todos** los estados, también aprobadas).
- **Solo API:** `GET /api/v2/customer/{customer_id}/orders` con **Bearer** (`token` en sesión, mismo que tras `v2/user/token`). `per_page` hasta **150**, `page` 1. **Sin filtro por estado** en el MVC.
- **Sin consulta directa a BD en esta pantalla** (temporal): si no hay token, la API falla o la respuesta no es válida, la tabla queda **vacía** y se registra error en log.

### Lógica en `Customer_portal::orders()`

1. **`fetchPortalOrdersFromApi`** — Guzzle al endpoint anterior; devuelve **todas** las órdenes devueltas por la API (cualquier estado). Mapea cada ítem a `order_id`, `order_number`, `created`, `status_label`, `total_amount`. Cualquier fallo → array vacío.

### Vista `customer/orders.php`

- **Sin acceso a base de datos:** solo renderiza `$orders`, enlaces (`url_menu`, `url_catalog`, `url_orders`, `url_new_order`, `url_logout`) y pestañas (**Catálogo**, **Órdenes**); textos vía **`cmp-customer-portal`** (p. ej. `orders_page_title`, `orders_add_button`, `back_to_menu`, `empty_orders`, `column_order_number`, `column_date`, `column_status`, `column_total`).
- **Nueva orden:** el botón **+** apunta a **`/customer/orders/new`** (`startOrder` en el controlador), no a la API desde el navegador en esta pantalla.

### Resumen

| Capa | Rol en `/customer/orders` |
|------|---------------------------|
| Controlador | Solo **HTTP** a `v2/customer/{id}/orders` con Bearer (sin SQL local en órdenes). |
| Vista | Presentación; **no** ejecuta SQL ni `fetch` a API. |

### API `GET /api/v2/customer/{customer}/orders` (listado portal)

- **`CustomerController::orders`:** comprueba que el usuario autenticado pueda ver ese cliente: **admin**, o vínculo en **`customer_user`**, o **manager/seller** de la `company_id` del cliente. Si no → **403** con `code: customer_orders_forbidden`.
- **Paginación:** `per_page` acotado entre 1 y **150** (el portal pide 150).
- El portal MVC depende solo de este endpoint para el listado (sin fallback BD por ahora).

## Solicitudes en el portal

- No existe la ruta **`/customer/requests`** en el MVC. La pestaña **Solicitudes** no está en el portal; la vista `customer/requests.php` no se usa.

## Controlador principal

- Archivo: `mvc/application/controllers/Customer_portal.php`
- Responsabilidades actuales:
  - Login del portal: ver sección **Login del portal**; API `v2/customer/login` y `v2/user/token`
  - Gestión de sesión de portal cliente (claves de sesión `customer_portal_*` en el controlador)
  - Carga de textos del bloque JSON `cmp-customer-portal` (con rutas candidatas y fallback MVC)
  - Render de vistas del portal (`catalog`, `orders`; `menu` solo redirige a catálogo)
  - Inicio de orden desde `orders/new`: requiere tabla `catalog_cart`; si no hay token o tabla, redirige a `customer/orders`
  - Logout de portal

## Vistas del portal

- `mvc/application/views/customer/login.php`
- `mvc/application/views/customer/menu.php`
- `mvc/application/views/customer/catalog.php`
- `mvc/application/views/customer/orders.php`

## Traducciones usadas por el portal

- Fuente principal: `api/lang/*.json` en bloque `cmp-customer-portal`
- Fallback MVC: `mvc/application/language/customer_portal/*.json`
- Claves agregadas recientes (entre otras):
  - `orders_add_button`
  - `logout_button`
  - `registration_*`
  - `reg_label_*`
  - `reg_status_*`

## Flujo funcional actual (alto nivel)

1. Login en `/customer/login` (API valida usuario cliente). Si ya hay sesión de portal, **GET** `/customer` o `/customer/login` redirige a **`/customer/catalog`**.
2. Tras login, redirección a **`/customer/catalog`** (primera pestaña del portal: **Catálogo**).
3. Pestañas: solo **Catálogo** y **Órdenes**.
   - **`/customer/menu`** y **`/customer/home`** (alias) redirigen a **`/customer/catalog`**.
   - **Catálogo** (`/customer/catalog`): registro del cliente y empresa (solo lectura); pestaña activa por defecto.
   - **Órdenes** (`/customer/orders`): listado **completo** de órdenes del cliente por API (todos los estados, incl. aprobadas) + acceso a nueva orden.
4. Crear orden desde `orders/new`:
   - genera/reutiliza token de sesión de carrito en `catalog_cart` y redirige a `catalogo/producto?token=...`

## Dependencias mixtas actuales

- API:
  - login cliente (`v2/customer/login` → `CustomerPortalLoginController`)
  - token de usuario (`v2/user/token`, vía `MY_Controller` / sesión, mismo criterio que company)
  - flujo de carrito/catálogo público tras `orders/new` (no listado aquí archivo por archivo)
- MVC/BD local:
  - varios listados y fallbacks en `Customer_portal`
  - lectura/escritura en `catalog_cart`, `companies`, `customers`, etc. según pantalla; **órdenes** (`/customer/orders`) **solo vía API** (listado completo).

## Comparativa: API HTTP frente a consulta directa a base de datos

Ámbito: **`Customer_portal.php` (MVC CodeIgniter)** y lo que dispara. La API Laravel (`CustomerPortalLoginController`, etc.) **sí usa base de datos**, pero desde el proceso PHP del MVC eso se ve solo como **HTTP**; aquí se indica qué hace cada capa desde el portal.

### Visión rápida

| Origen de datos | Qué cubre en el portal |
|-----------------|-------------------------|
| **API v2 (Guzzle desde MVC)** | Validar credenciales (`POST /v2/customer/login`) y guardar Bearer (`GET /v2/user/token`). |
| **BD vía CodeIgniter (`$this->db` y modelos cargados)** | Tras el login: empresa y cliente en catálogo, carrito `catalog_cart`, catálogo por defecto, sesión de carrito en tabla legacy (`session_cart` u otra según `cartSessionTableName()`). **Órdenes** en portal → API. |
| **Ficheros JSON en disco** | Cadenas `cmp-customer-portal` (`file_get_contents` sobre rutas candidatas); no es API ni SQL desde el controlador. |
| **Redirección al catálogo público** | `catalogo/producto?token=...` — el armado del pedido en UI corre fuera de este controlador; el token se prepara aquí leyendo/insertando en BD. |

### Detalle por pantalla / flujo (MVC)

| Pantalla o acción | ¿Llama API? | ¿Consulta directa BD (CI)? | Tablas / notas |
|-------------------|------------|-----------------------------|----------------|
| **Login POST** (`portalLoginJson`) | Sí: `v2/customer/login`, luego `v2/user/token` | No | Credenciales no se validan en SQL dentro del MVC. |
| **Menú** (`menu`) | No | No | Redirección HTTP a `/customer/catalog`; sin vista propia. |
| **Catálogo (registro)** (`catalog`) | No | Sí | `companies`, `customers` (y `deleted_at` si existe). |
| **Órdenes** (`orders`) | **Sí** (`GET v2/customer/{id}/orders`, todas las órdenes) | **No** | Solo datos de la API. |
| **Solicitudes** | — | — | Sin ruta MVC `/customer/requests`; pestaña no expuesta en el portal. |
| **Nueva orden** (`startOrder`) | No | Sí | `catalog_cart` (reutilizar o insertar fila), lectura `catalog` para `catalog_id` por defecto; si falla, redirect sin API. |
| **Sesión al loguear** (`establishCustomerSession`) | No | Sí (opcional) | Última fila en tabla de sesión de carrito (`getLastCartSession` / `saveCartSession`). |
| **Idioma portal** (`loadPortalLangSection*`) | No | No | Lectura de archivos `.json`; no SQL. |
| **Logout** | No | No | Limpia sesión PHP / datos de usuario en memoria. |

### Lectura útil para revisiones

- **Autenticación**: centralizada en API (reglas, usuario cliente, `company_id` / `customer_id` coherentes con Laravel).
- **Contenido del portal autenticado**: **SQL directo** (catálogo, carrito, etc.) y **API** para el **listado de órdenes** (todas).
- **Riesgo / mantenimiento**: listado de pedidos del portal depende de **`CustomerController::orders`**.

## Estructura de lo modificado (archivos según este documento)

Inventario alineado con las secciones anteriores: rutas, controlador, vistas, idiomas y API de login. Útil para revisiones (p. ej. con Calam), no como historial de Git.

### 1. Rutas MVC

| Archivo |
|--------|
| `mvc/application/config/routes.php` |

Rutas bajo prefijo `customer` → `customer_portal/*` (ver tabla en la primera sección).

### 2. Controlador del portal

| Archivo |
|--------|
| `mvc/application/controllers/Customer_portal.php` |

### 3. Vistas del portal

| Archivo |
|--------|
| `mvc/application/views/customer/login.php` |
| `mvc/application/views/customer/menu.php` |
| `mvc/application/views/customer/catalog.php` |
| `mvc/application/views/customer/orders.php` |

### 4. Traducciones — API (`cmp-customer-portal` en JSON raíz)

Mismo bloque de claves en cada locale:

| Archivo |
|--------|
| `api/lang/de-DE.json` |
| `api/lang/en-EN.json` |
| `api/lang/es-ES.json` |
| `api/lang/it-IT.json` |
| `api/lang/pt-PT.json` |
| `api/lang/ru-RU.json` |
| `api/lang/zh-CN.json` |

### 5. Traducciones — fallback MVC

Copia / respaldo del bloque `cmp-customer-portal` para cuando no se resuelva el JSON de API:

| Archivo |
|--------|
| `mvc/application/language/customer_portal/de-DE.json` |
| `mvc/application/language/customer_portal/en-EN.json` |
| `mvc/application/language/customer_portal/es-ES.json` |
| `mvc/application/language/customer_portal/it-IT.json` |
| `mvc/application/language/customer_portal/pt-PT.json` |
| `mvc/application/language/customer_portal/ru-RU.json` |
| `mvc/application/language/customer_portal/zh-CN.json` |

### 6. API Laravel (login portal cliente)

| Archivo | Notas |
|--------|--------|
| `api/app/Http/Controllers/CustomerPortalLoginController.php` | Lógica de `POST v2/customer/login` |
| `api/routes/api.php` | Registro de la ruta `customer/login` dentro del prefijo `v2` |

### 7. Documentación

| Archivo |
|--------|
| `docs/customer/CUSTOMER_PORTAL_ESTRUCTURA.md` |

