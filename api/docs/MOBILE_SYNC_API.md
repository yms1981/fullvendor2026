# APIs Mobile Sync — `/api/v3/movilregister` y `/api/v3/movilsync`

Documentación de sincronización de la app móvil (Swagger tag: **MobileSync**).

UI Swagger: `/api/v2/documentation` (también `/swagger` y `/api-docs`).

## Autenticación

**Sanctum Bearer token** (mismo login que `/api/v3/auth/login`).

```http
Authorization: Bearer {token}
Accept: application/json
Accept-Language: es
```

## Tablas

| Tabla | Rol |
|-------|-----|
| `mobile_devices` | Dispositivos que acceden a FullVendor (`device_uuid`, usuario, compañía, `last_used_at`, `last_sync_at`) |
| `mobile_sync_logs` | Cada solicitud de sync (hora/fecha, modo total/parcial, conteos por grupo) |
| `mobile_change_log` | Ítems modificados por grupo: `products`, `customers`, `categories`, `orders` |

Umbral de sync **total**: más de **500** cambios desde `last_sync_at`, o `last_sync_at` nulo (nunca sincronizó).

Los observers de `Product`, `Customer`, `Category` y `Order` escriben automáticamente en `mobile_change_log`.

---

## Flujo recomendado

1. Login → token.
2. `POST /api/v3/movilregister` (alta o update de último uso).
3. `POST /api/v3/movilsync` → responde `sync_mode: total|partial`.
4. Si **total**: el cliente descarga catálogos/listados completos por los endpoints normales de `/api/v3/...`.
5. Si **parcial**: llama a `/api/v3/movilsync/{products|customers|categories|orders}`.
6. `POST /api/v3/movilsync/complete` → actualiza `last_sync_at`.

---

## Endpoints

| Método | Path | Descripción |
|--------|------|-------------|
| POST | `/api/v3/movilregister` | Registrar o actualizar dispositivo |
| POST | `/api/v3/movilsync` | Decidir sync total vs parcial |
| GET/POST | `/api/v3/movilsync/products` | Deltas de productos |
| GET/POST | `/api/v3/movilsync/customers` | Deltas de clientes |
| GET/POST | `/api/v3/movilsync/categories` | Deltas de categorías |
| GET/POST | `/api/v3/movilsync/orders` | Deltas de órdenes |
| POST | `/api/v3/movilsync/complete` | Marcar sync completada |

---

## movilregister

```bash
curl -s -X POST "http://localhost/api/v3/movilregister" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "device_uuid": "A1B2-DEVICE-UUID",
    "company_id": 1,
    "device_name": "Pixel 8",
    "device_type": "android",
    "os_version": "14",
    "app_version": "2.1.0"
  }' | jq .
```

**200:**

```json
{
  "success": true,
  "message": "Dispositivo registrado.",
  "data": {
    "created": true,
    "device": {
      "id": 1,
      "device_uuid": "A1B2-DEVICE-UUID",
      "user_id": 10,
      "company_id": 1,
      "last_used_at": "2026-10-22T02:00:00+00:00",
      "last_sync_at": null
    }
  }
}
```

Si el `device_uuid` ya existe: actualiza datos y `last_used_at` (`created: false`).

---

## movilsync

```bash
curl -s -X POST "http://localhost/api/v3/movilsync" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_uuid":"A1B2-DEVICE-UUID","company_id":1}' | jq .
```

**200 — parcial:**

```json
{
  "success": true,
  "message": "Puede realizar una sincronización parcial.",
  "data": {
    "sync_mode": "partial",
    "is_total": false,
    "is_partial": true,
    "never_synced": false,
    "changes_count": 42,
    "threshold": 500,
    "since_at": "2026-10-20T12:00:00+00:00",
    "groups": {
      "products": 10,
      "customers": 5,
      "categories": 2,
      "orders": 25
    },
    "sync_log_id": 15,
    "endpoints": {
      "products": "/api/v3/movilsync/products",
      "customers": "/api/v3/movilsync/customers",
      "categories": "/api/v3/movilsync/categories",
      "orders": "/api/v3/movilsync/orders",
      "complete": "/api/v3/movilsync/complete"
    }
  }
}
```

**200 — total** (`never_synced` o `changes_count > 500`):

```json
{
  "data": {
    "sync_mode": "total",
    "is_total": true,
    "is_partial": false,
    "never_synced": true,
    "changes_count": null,
    "threshold": 500
  }
}
```

Cada llamada deja un registro en `mobile_sync_logs`.

---

## Deltas parciales (ejemplo productos)

```bash
curl -s "http://localhost/api/v3/movilsync/products?device_uuid=A1B2-DEVICE-UUID&company_id=1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" | jq .
```

**200:**

```json
{
  "success": true,
  "data": {
    "group": "products",
    "since_at": "2026-10-20T12:00:00+00:00",
    "count": 2,
    "items": [
      {
        "entity_id": 3222,
        "action": "updated",
        "changed_at": "2026-10-21T18:00:00+00:00",
        "data": {
          "pro_id": 3222,
          "sku": "MU120214",
          "name": "BEADS",
          "sale_price": 1.5,
          "status": 1
        }
      },
      {
        "entity_id": 999,
        "action": "deleted",
        "changed_at": "2026-10-21T19:00:00+00:00",
        "data": null
      }
    ]
  }
}
```

Si el dispositivo nunca sincronizó (`last_sync_at` null) estos endpoints responden **422** (`requires_total_sync`).

---

## Completar sync

```bash
curl -s -X POST "http://localhost/api/v3/movilsync/complete" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_uuid":"A1B2-DEVICE-UUID","sync_log_id":15}' | jq .
```

Actualiza `mobile_devices.last_sync_at` y marca el log como `completed`.

---

## OpenAPI

```bash
php artisan openapi:sync --scan=Mobile
```

Tag **MobileSync** en `/api/v2/documentation`.
