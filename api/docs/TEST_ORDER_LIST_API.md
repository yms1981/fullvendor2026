# Cómo comprobar el API GET /api/v2/order/list

## 1. Verificar que la ruta existe

Desde la raíz del proyecto API:

```bash
cd api
php artisan route:list --path=order
```

Deberías ver una línea con método `GET`, URI `api/v2/order/list` y acción `OrderController@list`.

---

## 2. Probar con curl (terminal)

Necesitas un **token de autenticación** válido (Bearer token) de un usuario que tenga compañía asignada.

### Obtener token (login)

```bash
curl -s -X POST http://localhost/api/v2/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tu_email@ejemplo.com","password":"tu_password"}' \
  | jq -r '.token'
```

Guarda el valor en una variable o cópialo.

### Llamar al listado de órdenes

```bash
# Sustituye YOUR_TOKEN por el token obtenido
curl -s -X GET "http://localhost/api/v2/order/list" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  | jq .
```

**Respuesta esperada (200 OK):**

- Si el usuario tiene compañía y hay órdenes:
  - `data`: array de órdenes, cada una con `customer`, `seller`, `warehouse` (si aplica), `status_name`, `order_details` con productos (sku, descriptions, unit).

- Si el usuario no tiene compañía:
  - **403** con `"message": "User has no company associated."`

- Si el token es inválido o falta:
  - **401** Unauthenticated.

---

## 3. Probar con Postman (o Insomnia)

1. **Método:** GET  
2. **URL:** `http://localhost/api/v2/order/list`  
   (o la URL base de tu entorno: `https://tu-dominio.com/api/v2/order/list`)
3. **Headers:**
   - `Authorization`: `Bearer TU_TOKEN`
   - `Accept`: `application/json`
4. Enviar la petición.

Revisa que el JSON de respuesta tenga:

- `data`: array de objetos.
- Cada objeto con: propiedades de la orden, `customer`, `seller`, `status_name`, `order_details`.
- Cada ítem de `order_details` con `product` que incluya `sku`, `descriptions`, `unit` (unit_id, unit, description).
- `warehouse` solo si la orden tiene almacén asignado.

---

## 4. Comprobar desde el navegador (solo si tienes token en sesión)

Si tu front ya guarda el token (localStorage, cookie, etc.), puedes hacer la petición desde la consola del navegador:

```javascript
fetch('/api/v2/order/list', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('token'), // o donde guardes el token
    'Accept': 'application/json'
  }
})
.then(r => r.json())
.then(console.log);
```

---

## 5. Resumen de qué validar

| Comprobación | Cómo |
|--------------|------|
| Ruta registrada | `php artisan route:list --path=order` |
| Solo órdenes de la compañía del usuario | El `company_id` de cada orden debe ser el de la compañía del token |
| Cliente en cada orden | Cada ítem de `data` tiene objeto `customer` |
| Seller en cada orden | Cada ítem tiene objeto `seller` |
| Nombre del estado | Cada ítem tiene `status_name` (string) |
| Productos con sku, descripción, unidad | En `order_details[].product`: `sku`, `descriptions`, `unit` (con `unit`, `description`) |
| Warehouse cuando aplica | Si la orden tiene almacén, existe `warehouse` en el objeto |
| Sin token / token inválido | Respuesta 401 |
| Usuario sin compañía | Respuesta 403 con mensaje de “no company” |

Si algo de esto falla, revisa logs en `api/storage/logs/laravel.log` y la configuración de CORS y autenticación en tu entorno.
