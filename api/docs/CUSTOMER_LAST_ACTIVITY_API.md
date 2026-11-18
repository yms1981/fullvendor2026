# Customer — últimos catálogos y órdenes

Auth: Sanctum (`Authorization: Bearer {token}`).

Usados en la edición de cliente (`/company/customers/edit/{id}`), debajo de **ECommerce Portal Users**.

## Endpoints

| Método | Path | Descripción |
|--------|------|-------------|
| GET | `/api/v2/catalogcart/company/{companyId}/customer/{customerId}/recent?limit=5` | Últimos catálogos enviados (máx. 20) |
| POST | `/api/v2/catalogcart/company/{companyId}/cart/{cartId}/expire` | Expira el link del catálogo |
| GET | `/api/v2/order?customer_id={id}&per_page=5&order_sort=created&order_type=DESC` | Últimas órdenes del cliente |

## Ejemplo: recientes catálogos

```bash
curl -s -H "Authorization: Bearer TOKEN" \
  "https://HOST/api/v2/catalogcart/company/1/customer/526/recent?limit=5"
```

Respuesta 200:

```json
{
  "data": [
    {
      "cart_id": 12,
      "catalog_id": 3,
      "catalog_name": "FullVendor Catalog",
      "default_catalog": 1,
      "created": "2026-11-08 01:11:00",
      "expired_date": "2027-01-07 01:01:00",
      "token_kor": "...",
      "view": 1
    }
  ]
}
```

## Ejemplo: expirar link

```bash
curl -s -X POST -H "Authorization: Bearer TOKEN" \
  "https://HOST/api/v2/catalogcart/company/1/cart/12/expire"
```

Respuesta 200: `{ "success": true }`  
Respuesta 404: carrito no encontrado.
