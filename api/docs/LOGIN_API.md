# API Login — `/api/v2/login`

Autenticación Fortify (email + password). Swagger tag: **Auth**. UI: `/api/v2/documentation`.

## Autenticación

Endpoint **público** (sin Bearer). Tras un login correcto se emite un token Sanctum en `user.token`.

---

## Endpoint

| Método | Path | Descripción |
|--------|------|-------------|
| POST | `/api/v2/login` | Login; respuesta con usuario, accesos y compañías |

### Request

```bash
curl -s -X POST "http://localhost/api/v2/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret","device_name":"mvc"}' | jq .
```

| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|--------|
| `email` | string | sí | |
| `password` | string | sí | |
| `device_name` | string | no | Nombre del token Sanctum (default `mvc`) |

### Respuesta 200

Campos históricos (`user`, `app_access`, `companies`, `roles`, `abilities`, `token`) **sin cambios de contrato**.

Campos **añadidos**:

| Campo | Ubicación | Descripción |
|-------|-----------|-------------|
| `avatar_url` | `user` | URL absoluta del avatar del usuario (`null` si no hay imagen) |
| `avatar_url` | cada ítem de `companies` y de `app_access.*.companies` | URL absoluta del logo/avatar de la compañía |

Ejemplo (recortado):

```json
{
  "user": {
    "id": 1,
    "name": "Ana",
    "email": "ana@example.com",
    "image": "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee.webp",
    "avatar_url": "http://localhost/api/v2/images/aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee.webp",
    "token": "1|..."
  },
  "app_access": {
    "admin": { "access": false },
    "company": {
      "access": true,
      "companies": [
        {
          "company_id": 10,
          "name": "Mi Empresa",
          "avatar_url": "http://localhost/api/v2/images/ffffffff-1111-2222-3333-444444444444.jpg",
          "roles": { "type": "manager", "abilities": [] }
        }
      ]
    }
  },
  "companies": [
    {
      "company_id": 10,
      "name": "Mi Empresa",
      "avatar_url": "http://localhost/api/v2/images/ffffffff-1111-2222-3333-444444444444.jpg",
      "roles": { "type": "manager", "abilities": [] }
    }
  ]
}
```

La base de las URLs usa `FULLVENDOR_WEB_URL` (o `APP_URL`). Valores vacíos → `avatar_url: null`.

### Errores

| HTTP | Cuándo |
|------|--------|
| 422 | Validación / credenciales inválidas (Fortify) |

---

## Móvil (`/api/v3/auth/login`)

Misma lógica de avatares en el payload interno (`user.avatar_url`, `companies[].avatar_url`). El envelope móvil (`success` / `message` / `data`) no cambia.
