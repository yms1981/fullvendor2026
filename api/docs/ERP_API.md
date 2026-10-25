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

---

## Endpoints

| Método | Path | Descripción |
|--------|------|-------------|
| GET | `/api/v2/erp/ping` | Comprueba que la API key es válida |

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
