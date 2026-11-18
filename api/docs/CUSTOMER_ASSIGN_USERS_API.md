# Customer — usuarios asociados y contraseña portal

Auth: Sanctum (`Authorization: Bearer {token}`).

## Alta / actualización automática de usuario portal

El sistema verifica **siempre** (no solo al editar) que un cliente con `email` tenga usuario portal:

| Momento | Dónde |
|---------|--------|
| Alta / update | `CustomerPortalUserObserver` + `saveCustomerData` |
| Show / edit | `CustomerController::show` |
| Listado clientes | `CustomerController::index` (`ensureMissing` por compañía) |
| Cron horario | `php artisan customers:ensure-portal-users` |

1. Si no existe un registro en `users` con ese email → se crea automáticamente (rol `customer`).
2. Contraseña: `portal_password` del request si viene; si password/`password_plain` están vacíos → `12345678`.
3. En el alta MVC (`customers/add`), si hay email la contraseña es **obligatoria**.
4. Se vincula en `customer_user` y `company_users` (type `customer`).
5. Si el usuario ya existía con contraseña válida → **no** se sobrescribe; solo se aseguran los vínculos.

Implementación: `EnsureCustomerPortalUserService` + `CustomerPortalUserObserver`.

| Método | Path | Descripción |
|--------|------|-------------|
| POST | `/api/v2/customer/assignUser/{customer}` | Asigna un usuario al cliente |
| POST | `/api/v2/customer/unassignUser/{customer}` | Quita un usuario asociado del cliente |
| POST | `/api/v2/customer/{customer}/portal-password` | Cambia la contraseña del usuario `users` con el mismo email del cliente (hash + `password_plain`) |
| POST | `/api/v2/customer/{customer}/send-portal-credentials` | Envía credenciales por `email` \| `sms` \| `whatsapp` (links ECommerce si hay + Customer Portal `/customer` + email/password) |

## Body (ambos)

```json
{
  "company_id": 1,
  "user_id": 42
}
```

## Ejemplo assign

```bash
curl -X POST "$API/api/v2/customer/assignUser/123" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1,"user_id":42}'
```

**201** — usuario asignado  
**422** — validación

## Ejemplo unassign

```bash
curl -X POST "$API/api/v2/customer/unassignUser/123" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1,"user_id":42}'
```

**200** — usuario desasignado  
**422** — validación

## Ejemplo portal-password

```bash
curl -X POST "$API/api/v2/customer/123/portal-password" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"password":"NuevaClave123","password_confirmation":"NuevaClave123"}'
```

**200** — `{ "message": "...", "password_plain": "NuevaClave123" }`  
**422** — validación o sin usuario `users` con el email del cliente

La contraseña se guarda hasheada en `users.password` y en claro en `users.password_plain`.

## Ejemplo send-portal-credentials

```bash
# Email (por defecto)
curl -X POST "$API/api/v2/customer/123/send-portal-credentials" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"user_id":42,"channel":"email"}'

# SMS
curl -X POST "$API/api/v2/customer/123/send-portal-credentials" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"user_id":42,"channel":"sms"}'

# WhatsApp
curl -X POST "$API/api/v2/customer/123/send-portal-credentials" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"user_id":42,"channel":"whatsapp"}'
```

**Body:** `user_id` (required), `channel` opcional: `email` (default) | `sms` | `whatsapp`

**200** — `{ "message": "...", "channel": "email|sms|whatsapp", "email": "...", "phone": "...|null", "ecommerce_url": "...|null", "customer_portal_url": "https://host/customer" }`  
**422** — usuario no vinculado, sin email, sin teléfono (SMS/WhatsApp), sin prefijo país, o fallo de envío (Telecom)

El mensaje incluye:

1. Link **ECommerce Portal** solo si la compañía tiene `ecommerce_enabled` + `ecommerce_url`
2. Link **Customer Portal** → `{FULLVENDOR_WEB_URL|/APP_URL}/customer`
3. Email y contraseña (`password_plain`; si estaba vacío se usa `12345678`)

Para SMS/WhatsApp el teléfono se toma de `users.cellphone` / `users.phone`, o en su defecto `customers.cell_phone` / `customers.phone`, con prefijo del país comercial del cliente.
