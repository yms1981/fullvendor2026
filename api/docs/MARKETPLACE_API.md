# APIs Marketplace — `/api/v2/marketplace`

Documentación de la sección **Marketplace** (Swagger tag: `Marketplace`).

UI Swagger: `/api/v2/documentation`.

## Autenticación

**Pública: sin token.** Solo rate limit (`throttle:120,1`).

---

## Endpoints

| Método | Path | Descripción |
|--------|------|-------------|
| GET | `/api/v2/marketplace/home` | Home: empresas con catálogo, categorías por empresa y productos con foto |
| GET | `/api/v2/marketplace/companies` | Listado paginado de empresas marketplace |
| GET/POST | `/api/v2/marketplace/companies/search` | Buscar empresas por nombre |
| GET | `/api/v2/marketplace/companies/slug/{slug}` | Empresa por slug |
| GET | `/api/v2/marketplace/companies/{companyId}/categories` | Categorías de la empresa |
| GET | `/api/v2/marketplace/companies/{companyId}/products` | Productos por `category_id` |
| POST | `/api/v2/marketplace/products/by-category` | Productos por categoría (body) |

---

## Home feed

```bash
curl -s -X GET "http://localhost/api/v2/marketplace/home?companies=8&products_per_company=4&featured=12" \
  -H "Accept: application/json" | jq .
```

**Respuesta 200 (estructura):**

```json
{
  "data": {
    "companies": [
      {
        "company": { "company_id": 1, "name": "…", "slug_url": "…" },
        "categories": [
          {
            "cat_id": 10,
            "category_name": "…",
            "company_id": 1,
            "cover_image_uuid": "uuid"
          }
        ],
        "products": [
          {
            "pro_id": 1,
            "name": "…",
            "image_uuid": "uuid",
            "sale_price": 22,
            "currency_symbol": "$",
            "currency_code": "USD"
          }
        ]
      }
    ],
    "categories_by_company": [],
    "category_carousel": [
      {
        "cat_id": 2,
        "category_name": "Appliances & Others",
        "company_id": 1,
        "company_name": "Home Value LLC",
        "company_slug": "…",
        "image_uuid": "uuid-de-la-categoría",
        "cover_image_uuid": "uuid-categoría-o-producto"
      }
    ],
    "promo_products": [],
    "featured_products": []
  }
}
```

- `category_carousel`: categorías existentes con foto (`categories.image`) o, si falta, foto de un producto de esa categoría.
- Promociones y destacados **solo incluyen productos con imagen** de cada empresa.
- `currency_symbol` / `currency_code`: moneda de la **empresa** (`companies.currency` → `currency`), no del catálogo genérico.
