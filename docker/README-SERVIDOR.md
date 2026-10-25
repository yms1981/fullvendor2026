# Docker en servidor

## 1. Construir la imagen del MVC (PHP + Apache)

En la **raíz del repo** (`fullvendor2025`):

```bash
# Producción (recomendado: sin xdebug, más ligera)
docker build -f docker/Dockerfile --target prod -t fullvendor/mvc-php:7.4-prod ./docker
```

O con Compose:

```bash
docker compose -f compose.prod.yaml build fullvendor
```

## 2. Levantar todo en el servidor

1. Clona el repo en el servidor (el código se monta en el contenedor).
2. Crea un `.env` en la raíz o exporta variables:

   - `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` (obligatorias en `compose.prod.yaml`)
   - `BASE_URL` (URL pública, ej. `https://tudominio.com`)
   - `CI_ENV=production`
   - `COOKIE_DOMAIN` si usas subdominios
   - `MVC_TRUST_PROXY_HTTPS=1` si hay nginx/traefik con TLS delante del contenedor

**Importante (Mixed Content / login bloqueado):** `BASE_URL` debe ser **`https://…`**, nunca `http://…`, si el sitio se abre por HTTPS. El proxy TLS debe reenviar `X-Forwarded-Proto`:

```nginx
proxy_set_header Host $host;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
```

Si el formulario de login apunta a `http://…` con la página en `https://…`, el navegador bloquea el POST (“Mixed Content”) y el login no completa.
3. Arranca:

```bash
docker compose -f compose.prod.yaml up -d
```

La API Laravel suele ir en **otro host o contenedor** (Sail, otro compose en `api/`). Configura en el MVC la URL de la API (`database.php` / env) para que apunte a ese servicio.

## 3. Publicar la imagen en un registro (opcional)

Si quieres **no** hacer `build` en el servidor:

```bash
docker tag fullvendor/mvc-php:7.4-prod REGISTRO/tu-org/fullvendor-mvc:7.4-prod
docker login REGISTRO
docker push REGISTRO/tu-org/fullvendor-mvc:7.4-prod
```

En el servidor, en `compose.prod.yaml` cambia la sección `build` por:

```yaml
image: REGISTRO/tu-org/fullvendor-mvc:7.4-prod
# y quita build:
```

## 4. Imágenes que no construyes tú

- `mariadb:10.6.16-focal`
- `memcached:alpine`

Se descargan solas con `docker compose pull` / `up`.

## 5. Desarrollo local (con xdebug)

Sigue usando el `compose.yaml` habitual (`target: dev`).

---

## 6. Mismo stack que Sail, pero con imágenes propias (servidor / Webmin)

**Sail** construye la imagen `sail-8.3/app` desde `vendor/laravel/sail` (solo tras `composer install` en `api/`). En el servidor no hace falta Sail: usamos una imagen equivalente definida en el repo.

### Construir MVC + API de una vez

```bash
./scripts/build-imagenes-servidor.sh
```

O manual:

```bash
docker build -f docker/Dockerfile --target prod -t fullvendor/mvc-php:7.4-prod ./docker
docker build -f api/docker/Dockerfile -t fullvendor/api:8.3 ./api
```

### Subir imágenes al registry y desplegar

```bash
docker tag fullvendor/api:8.3 TU_REGISTRY/fullvendor-api:8.3
docker tag fullvendor/mvc-php:7.4-prod TU_REGISTRY/fullvendor-mvc:7.4
docker push TU_REGISTRY/fullvendor-api:8.3
docker push TU_REGISTRY/fullvendor-mvc:7.4
```

En el servidor: `docker pull ...` y en los compose usa `image: TU_REGISTRY/...` (sin `build:` si no quieres compilar allí).

### API en servidor (sin Sail)

Desde la carpeta **`api/`** (con tu `.env` de producción, `DB_HOST=mariadb` si usas el compose incluido):

```bash
cd api
docker compose -f compose.servidor.yaml build   # o solo pull si ya subiste la imagen
docker compose -f compose.servidor.yaml up -d
```

La API queda en el puerto **8081** por defecto (`API_HTTP_PORT`). Ajusta en el MVC la URL base de la API (ej. `https://api.tudominio.com` o `http://IP_SERVIDOR:8081`).

Tras el primer `up`, sincroniza la BD desde el repo (no uses migraciones Laravel sueltas):

```bash
cd api
docker compose -f compose.servidor.yaml exec api php artisan schema:sync --dry-run
docker compose -f compose.servidor.yaml exec api php artisan schema:sync --force
docker compose -f compose.servidor.yaml exec api php artisan schema:audit
```

(`php artisan migrate --force` hace lo mismo que `schema:sync --force`.)

En el `.env` de la **API** (`api/.env`), si el catálogo público está en otro host que `APP_URL` (típico cuando `APP_URL` es solo la API), define **`FULLVENDOR_WEB_URL`** con la URL del MVC (la misma idea que `BASE_URL` del front), para que los correos de “enviar catálogo” enlacen a `…/catalogo/producto?token=…` en el sitio correcto.

### Webmin + Docker

1. **Terminal** en el servidor o **Webmin → Servers → Docker** (según módulo).
2. Clona el repo, coloca `api/.env` y variables del MVC.
3. Ejecuta los `docker compose` anteriores en la ruta correcta.
4. Opción **Compose**: indica la ruta absoluta a `api/compose.servidor.yaml` o al `compose.prod.yaml` de la raíz.

Si solo quieres replicar Sail en local para generar la imagen:

```bash
cd api && composer install
docker compose -f docker-compose.yml build laravel.test
docker tag sail-8.3/app mi-registry/laravel-sail:8.3
docker push mi-registry/laravel-sail:8.3
```

La imagen **`fullvendor/api:8.3`** del Dockerfile del repo suele ser más estable para CI/servidor (no depende de `vendor/laravel/sail` en el contexto de build).
