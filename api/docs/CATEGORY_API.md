# Category API

Auth: `Authorization: Bearer {token}` (Sanctum).

Base: `/api/v2/category`

## Endpoints

| Método | Path | Descripción |
|--------|------|-------------|
| GET | `/api/v2/category/all` | Todas las categorías de la compañía (sin paginación) |
| GET | `/api/v2/category/catalog` | Igual que `/all`, ordenadas como el catálogo **FullVendor** (`default_catalog=1` / `order_categories`) |

### GET `/api/v2/category/catalog`

Devuelve las categorías de la empresa en el orden del catálogo por defecto (FullVendor Catalog). La categoría default (`category_default=1`) queda al final. Antes de listar se sincroniza el catálogo FullVendor si hace falta.

**Query**

| Parámetro | Requerido | Descripción |
|-----------|-----------|-------------|
| `company_id` / `company` | No | Compañía; si se omite, se usa la del usuario autenticado |

**curl**

```bash
curl -s -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  "https://example.com/api/v2/category/catalog?company_id=123"
```

**200**

```json
{
  "data": [
    {
      "cat_id": 10,
      "category_name": "Beverages",
      "category_default": null,
      "imageurl": "/images/….webp",
      "has_photo": true,
      "has_image": true,
      "image_placeholder": "/images/no-image"
    }
  ]
}
```

**Errores**

| Código | Cuándo |
|--------|--------|
| 401 | Sin token / no autenticado |
| 422 | No se pudo determinar la compañía del usuario |
