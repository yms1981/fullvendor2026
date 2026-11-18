# Exportar imágenes de productos (ZIP)

Desde el listado de productos del panel company se puede descargar un ZIP con las imágenes de los productos seleccionados (o de todos los filtrados).

Auth: `Authorization: Bearer {token}` (Sanctum), el mismo login del panel.

## Endpoint

| Método | Ruta | Qué hace |
|---|---|---|
| POST | `/api/v2/product/export-images` | Genera un ZIP con las imágenes y lo descarga |

### Body (JSON)

| Campo | Tipo | Descripción |
|---|---|---|
| `products` | `array\|string` | IDs de producto, o `"todos"` para el resultado filtrado |
| `excluded_ids` | `array` | IDs a excluir cuando `products=todos` |
| `company` / `company_id` | `int` | Compañía (también se inyecta desde la sesión MVC) |

Los mismos filtros del listado (`terms`, categorías, estado, etc.) se aplican cuando `products=todos`.

### Respuesta 200

Archivo `application/zip` (`product_images_YYYYMMDD_HHMMSS.zip`).

Nombres dentro del ZIP (compatibles con la importación de imágenes):

- `SKU.jpg` — primera imagen
- `SKU_pic2.jpg` — segunda
- `SKU_pic3.webp` — tercera…

### Errores 422

| `error` | Cuándo |
|---|---|
| `product_images_export_no_selection` | Sin productos seleccionados |
| `product_images_export_empty` | Sin imágenes reales para empaquetar |
| `product_images_export_too_many` | Más de 5000 productos |

## Ejemplo

```bash
curl -s -X POST "http://localhost/api/v2/product/export-images" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/zip" \
  -H "Content-Type: application/json" \
  -d '{"company_id":1,"products":[101,102]}' \
  -o product_images.zip
```

## MVC

En el listado: seleccionar productos → **Exportar imágenes**.  
Proxy: `POST /mvc/company/products/exportImages/`.
