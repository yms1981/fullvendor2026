# Datos geográficos y códigos postales

## Tablas en `appfv`

| Tabla | Uso |
|---|---|
| `countries` | Países (ya existía) |
| `states` | Estados / departamentos por `country_id` |
| `cities` | Ciudades por `state_id` |
| `postal_codes` | Códigos postales (GeoNames CC BY 4.0) |

## Importar códigos postales

Fuente: [GeoNames Postal Codes](https://download.geonames.org/export/zip/) (licencia CC BY 4.0 — atribuir a https://www.geonames.org/).

```bash
# Un país (ej. Estados Unidos)
cd api && php artisan geo:import-postal --country=US --link

# Todos los países soportados por GeoNames (~1M+ filas)
php artisan geo:import-postal --all --truncate --link
```

El flag `--link` intenta enlazar `country_id`, `state_id` y `city_id` por ISO2 / nombre.

## Cascada en clientes

En alta/edición de cliente: País → Estado → Ciudad → ZIP (`locationCascadeSelects.js`).
ZIP permite valor libre (Select2 tags) si no está en la lista.

## Dirección mostrada (listado / delivery)

Orden fijo concatenado: **Address, State, City, ZipCode, Country**.

Si el campo `commercial_address` / `dispatch_address` trae la dirección completa (calle+ciudad+estado+zip), al guardar se **descompone** automáticamente a las columnas correspondientes y en `*_address` queda solo la calle (`CustomerAddressNormalizerService`).

Backfill de clientes existentes:

```bash
php artisan customers:normalize-addresses --company=1 --dry-run
php artisan customers:normalize-addresses --company=1
```

## Producción

La tabla `postal_codes` **no** viene en `seed_data`. Tras `schema:sync` hay que importar:

```bash
# Solo EE.UU. (rápido)
php artisan geo:import-postal --country=US --link

# Mundial (~1M+ filas; tarda)
php artisan geo:import-postal --all --truncate --link
```

Sin este import, el desplegable de ZIP queda vacío (aunque Select2 tags permite escribir a mano). Los ZIP ya guardados en `customers.commercial_zip_code` sí se muestran en el listado.
