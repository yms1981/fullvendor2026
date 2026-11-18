# Avisos del listado de productos

Al abrir el listado de productos del panel, el MVC llama a este endpoint. La compañía sale de la sesión (`company_id`).

Auth: `Authorization: Bearer {token}` (Sanctum), el mismo login del panel.

## Endpoint de resumen

| Método | Ruta | Qué hace |
|---|---|---|
| GET | `/api/v2/product/list-quality` | Cuenta códigos de barras repetidos y productos con precio de venta 0. Si hay stock negativo, lo deja en 0. Si faltan unidades, asigna la unidad por defecto. |

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
  "negative_stock_fixed": 1,
  "units_fixed": 4
}
```

`negative_stock_fixed` es la cantidad de productos cuyo `stock` era menor que 0 y quedó en 0 en esta llamada.

`units_fixed` es la cantidad de productos sin `unit_id` a los que se asignó la unidad por defecto de la compañía.

Errores: 401 sin token, 403 si el usuario no pertenece a la compañía, 422 si falta `company_id`.

## Modal de códigos de barras repetidos

Desde el aviso de barcodes duplicados se abre un modal que lista los productos **agrupados por código** y permite editar/guardar el barcode.

| Método | Ruta | Qué hace |
|---|---|---|
| GET | `/api/v2/product/duplicate-barcodes` | Grupos `{ barcode, count, products[] }` |
| PATCH | `/api/v2/product/{product}/barcode` | Actualiza solo el barcode del producto |

### Listar grupos

```bash
curl -s "http://localhost/mvc/api/v2/product/duplicate-barcodes?company_id=1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"
```

Respuesta 200:

```json
{
  "groups": [
    {
      "barcode": "7501234567890",
      "count": 2,
      "products": [
        {
          "pro_id": 10,
          "sku": "SKU-A",
          "name": "Producto A",
          "barcode": "7501234567890",
          "sale_price": 12.5,
          "status": 1,
          "image_url": "/api/v2/public/products/10/image"
        }
      ]
    }
  ],
  "group_count": 1,
  "product_count": 2
}
```

### Actualizar barcode

```bash
curl -s -X PATCH "http://localhost/mvc/api/v2/product/10/barcode" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1,"barcode":"7509999999999"}'
```

## Filtrar el listado desde los avisos

En el panel, el aviso de barcodes abre el modal de corrección. Precio 0 y stock ajustado siguen filtrando el listado con `quality` + `prdstatus=3`:

| Enlace | Query / UI | Resultado |
|---|---|---|
| Códigos repetidos | Modal agrupado | Productos que comparten barcode (editable) |
| Precio 0 | `quality=zero_price` | `sale_price = 0` |
| Stock negativo ajustado | `quality=stock_fixed` | IDs corregidos en la última revisión (caché ~6h) |
| Unidades asignadas | `quality=units_fixed` | IDs a los que se asignó la unidad por defecto (caché ~6h) |

Ejemplo API del listado filtrado:

```bash
curl -s "http://localhost/mvc/api/v2/product?company=1&quality=zero_price&prdstatus=3" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"
```

En el listado, cada producto muestra el texto del código de barras debajo del nombre (además del icono).
