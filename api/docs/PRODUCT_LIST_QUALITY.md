# Avisos del listado de productos

Al abrir el listado de productos del panel, el MVC llama a este endpoint. La compañía sale de la sesión (`company_id`).

Auth: `Authorization: Bearer {token}` (Sanctum), el mismo login del panel.

## Endpoint

| Método | Ruta | Qué hace |
|---|---|---|
| GET | `/api/v2/product/list-quality` | Cuenta códigos de barras repetidos y productos con precio de venta 0. Si hay stock negativo, lo deja en 0. |

Un código de barras repetido es el mismo valor (sin distinguir mayúsculas) en más de un producto de la compañía. Si el campo trae varios códigos separados por coma, punto y coma, espacio o `|`, cada uno se revisa por separado. No se cuentan productos eliminados.

## Ejemplo

```bash
curl -s "http://localhost/mvc/api/v2/product/list-quality?company_id=1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"
```

Respuesta 200:

```json
{
  "duplicate_products": 2,
  "duplicate_codes": 1,
  "zero_price": 3,
  "negative_stock_fixed": 1
}
```

`negative_stock_fixed` es la cantidad de productos cuyo `stock` era menor que 0 y quedó en 0 en esta llamada.

Errores: 401 sin token, 403 si el usuario no pertenece a la compañía, 422 si falta `company_id`.
