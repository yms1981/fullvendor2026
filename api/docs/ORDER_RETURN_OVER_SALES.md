# Devoluciones mayores que las ventas

Comprueba, por cliente y producto, que la cantidad en notas de crédito (`tipo_d = C`) no supere la cantidad vendida (`tipo_d = D`). No entran órdenes canceladas, rechazadas ni en carrito.

## Endpoint

`GET /api/v2/order/return-over-sales`

Auth: Bearer de la compañía. Query opcional: `company_id`.

```bash
curl -s -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v2/order/return-over-sales?company_id=1"
```

Respuesta 200:

```json
{
  "count": 1,
  "items": [
    {
      "customer_id": 10,
      "customer_name": "CLIENTE",
      "product_id": 20,
      "sku": "SKU",
      "product_name": "Producto",
      "qty_sold": 8,
      "qty_credited": 12,
      "qty_excess": 4
    }
  ]
}
```

Al crear una nota de crédito (`POST /api/v2/order` con `tipo_d = C`, o la nota de crédito de almacén), si algún producto del cliente quedaría por encima de lo vendido, la API responde 422 con `error: return_exceeds_sales` y `items`.

## PDF por cliente

`GET /api/v2/order/return-over-sales/pdf?customer_id={id}`

Auth: Bearer de la compañía. Query: `customer_id` obligatorio, `company_id` opcional.

El PDF incluye el resumen de productos con exceso (vendido, devuelto, exceso) y, debajo, cada nota de crédito que contiene esos productos, con cantidad y precio de la línea.

```bash
curl -s -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v2/order/return-over-sales/pdf?company_id=1&customer_id=10" \
  -o excesos.pdf
```

404 si ese cliente no tiene excesos. 422 si falta `customer_id`.
