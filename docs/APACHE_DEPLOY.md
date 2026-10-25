# Despliegue en Apache

El repositorio tiene dos aplicaciones. Apache debe publicar cada una con su propia raíz de documentos. No sirve la API desde la carpeta de CodeIgniter.

| Aplicación | Código | Raíz pública (DocumentRoot) | Quién la usa |
|---|---|---|---|
| Panel web | `mvc/` (CodeIgniter) | La raíz del repositorio, o `mvc/` si el sitio vive en un subdirectorio | Navegador |
| API | `api/` (Laravel) | **`api/public`** | Panel, app móvil, otro sitio, ERP |

En XAMPP, `http://localhost/mvc/api/v2/documentation` entra por `api-front.php` y muestra Swagger. En el servidor, la documentación se publica en el host de la API (`https://api.ejemplo.com/api/v2/documentation`), con `DocumentRoot` en `api/public`.

## URLs para apps externas

Sustituye `https://api.ejemplo.com` por el host real de la API.

| Uso | URL |
|---|---|
| Swagger | `https://api.ejemplo.com/api/v2/documentation` |
| Atajos | `https://api.ejemplo.com/swagger` y `https://api.ejemplo.com/api-docs` |
| OpenAPI JSON | `https://api.ejemplo.com/api/v2/docs?api-docs.json` |
| Login del sitio web | `POST https://api.ejemplo.com/api/v2/login` |
| Login de la app móvil | `POST https://api.ejemplo.com/api/v3/auth/login` |
| Sync móvil | `POST https://api.ejemplo.com/api/v3/movilregister` y `/api/v3/movilsync` |
| ERP | `https://api.ejemplo.com/api/v2/erp/...` |

## public_html con `mvc/` y `api/` al lado (como en el servidor)

Si subes el repositorio tal cual y el dominio apunta a esa carpeta (`public_html/index.php`, `public_html/mvc`, `public_html/api`), en **test1.fullvendor.com** queda así:

- Panel: `https://test1.fullvendor.com/`
- API: `https://test1.fullvendor.com/api/v2/...` y `https://test1.fullvendor.com/api/v3/...`
- Swagger: `https://test1.fullvendor.com/api/v2/documentation`
- El `.htaccess` del repo trae `RewriteBase /mvc/` porque en XAMPP el sitio vive en `http://localhost/mvc/`. En el servidor **sustitúyelo** por `deploy/public_html.htaccess` (cópialo como `public_html/.htaccess`).

`mvc/.env` en el servidor:

```env
API_SERVER=test1.fullvendor.com
API_INTERNAL_BASE_URL=https://test1.fullvendor.com
BASE_URL=https://test1.fullvendor.com/
CI_ENV=production
MVC_TRUST_PROXY_HTTPS=1
```

`api/.env`: `APP_URL=https://test1.fullvendor.com`, `APP_DEBUG=false`, `APP_ENV=production`, y la base de datos de ese servidor. Sin `mvc/application/config/config.php` (no está en git) el panel no arranca: hay que copiarlo del entorno local.

En XAMPP la API va en el mismo Apache, puerto 80:

- Swagger del panel: `http://localhost/mvc/api/v2/documentation`
- Base que ya usan las apps: `http://localhost/api/v2/...`

Un teléfono en otra red no llega a `localhost`. En el servidor el host tiene que ser el dominio público (`https://api.ejemplo.com` o el mismo dominio con el proxy del apartado 4).

## Autenticación

Todas las peticiones llevan `Accept: application/json`.

### Sitio web (usuario)

```http
POST /api/v2/login
Content-Type: application/json

{"email":"usuario@empresa.com","password":"...","device_name":"sitio-web"}
```

La respuesta trae `user.token`. El resto de llamadas:

```http
Authorization: Bearer {user.token}
```

### App móvil

```http
POST /api/v3/auth/login
Content-Type: application/json

{"email":"usuario@empresa.com","password":"..."}
```

El token va en `data.user.token` (`data` es el mismo paquete que el login v2). Luego:

```http
Authorization: Bearer {token}
Accept-Language: es
```

Orden de sincronización: login, `POST /api/v3/movilregister`, `POST /api/v3/movilsync`, y si el modo es parcial los recursos `/api/v3/movilsync/{products|customers|categories|orders}`. Al terminar, `POST /api/v3/movilsync/complete`.

### ERP

No usa el token de usuario. Cada ruta bajo `/api/v2/erp` exige:

```http
X-Api-Key: {ERP_API_KEY}
```

El valor está en `api/.env` (`ERP_API_KEY`). Sin esa cabecera la API responde 401.

### Navegador en otro dominio

La app móvil nativa no pasa por CORS. Un sitio web abierto en el navegador, en otro dominio, sí. Si el navegador bloquea la petición, publica `config/cors.php` permitiendo el origen de ese sitio y los métodos y cabeceras `Authorization`, `Accept` y `Content-Type`.

Las imágenes que devuelve el login (`avatar_url`) usan `APP_URL` o `FULLVENDOR_WEB_URL`. Si `APP_URL` queda en `http://localhost`, el teléfono no podrá abrirlas. En producción debe ser la URL pública de la API, por ejemplo `https://api.ejemplo.com`.

## Requisitos del servidor

- Apache 2.4 con `rewrite`, `headers` y, si se usa el proxy del apartado alternativo, `proxy` y `proxy_http`.
- PHP **8.3** para la API y para el panel, con `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `curl`, `zip`, `intl` y **OPcache**.
- MariaDB o MySQL.
- Composer.
- Certificado TLS en producción. Las apps móviles deben llamar por `https://`.

OPcache en el `php.ini` de Apache:

```ini
zend_extension=opcache
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
```

Sin OPcache cada petición de Laravel tarda varios cientos de milisegundos solo en arrancar, y el panel las encadena.

No uses `php -S` en el servidor. Atiende una sola petición a la vez.

## 1. Código y dependencias

```bash
cd /var/www/fullvendor
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
cd api
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

En el panel, si no existe `mvc/.env`, créalo a partir de `mvc/.env.example`. `mvc/application/config/config.php` es local y no va en git: hace falta una copia en el servidor (la genera el entorno de desarrollo; sin ella CodeIgniter no arranca).

Permisos de escritura para el usuario de Apache (`www-data` o el que use el servicio):

- `api/storage`
- `api/bootstrap/cache`
- `mvc/application/cache`
- `mvc/application/logs`

## 2. Base de datos

En `api/.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://test1.fullvendor.com
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fullvendor
DB_USERNAME=fullvendor
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

`SESSION_DRIVER=database` y `CACHE_STORE=database` añaden consultas en cada petición. En el servidor, archivo o Redis van mejor.

Crear la base vacía y cargar esquema, datos fijos y vistas:

```bash
cd /home/test1-fv/public_html/api
php artisan schema:sync --force
```

Eso también comprueba el administrador de plataforma (`admin@fullvendor.com`). No ejecutes `schema:export` en un servidor: volcaría esa base encima de los scripts del repositorio.

## 3. VirtualHost de test1.fullvendor.com

No crees `fullvendor-api.conf` ni un host `api.ejemplo.com`. El sitio ya existe: `/etc/apache2/sites-available/test1.fullvendor.com.conf`. El panel y la API van en ese mismo dominio.

`DocumentRoot` debe ser la carpeta `public_html` (donde están `index.php`, `mvc/` y `api/`), no `api/public`. Quien separa el panel de Laravel es `public_html/.htaccess` (`deploy/public_html.htaccess`).

Ese `.conf` lleva **los dos puertos**. No borres ninguno y no dejes solo el 443.

- **Puerto 80:** entra quien escribe `http://test1.fullvendor.com`. Redirige al 443.
- **Puerto 443:** es el sitio de verdad, `https://test1.fullvendor.com`. Aquí está el certificado.

```apache
<VirtualHost 192.99.180.203:80>
    ServerName test1.fullvendor.com
    DocumentRoot /home/test1-fv/public_html

    RewriteEngine On
    RewriteRule ^ https://test1.fullvendor.com%{REQUEST_URI} [R=301,L]
</VirtualHost>

<VirtualHost 192.99.180.203:443>
    ServerName test1.fullvendor.com
    DocumentRoot /home/test1-fv/public_html

    <Directory /home/test1-fv/public_html>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>

    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1

    # Deja las líneas SSL que ese archivo ya tenga (certificado de test1.fullvendor.com).
</VirtualHost>
```

Si el archivo ya tiene un bloque `*:80` y otro `*:443`, no los reemplaces enteros. En los dos, `DocumentRoot` es `/home/test1-fv/public_html`. `AllowOverride All` va en el 443. El certificado del 443 se queda como está.

```bash
apachectl configtest
systemctl reload apache2
```

Caché de Laravel después de cambiar `api/.env`:

```bash
cd /home/test1-fv/public_html/api
php artisan config:cache
php artisan route:cache
```

## 4. Comprobar

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://test1.fullvendor.com/api/v2/documentation
curl -s -X POST https://test1.fullvendor.com/api/v2/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@empresa.com","password":"...","device_name":"prueba"}'
```

La documentación debe responder 200 y abrir Swagger. El login correcto responde 200 con `user.token`.

En el navegador: `https://test1.fullvendor.com/` es el panel y `https://test1.fullvendor.com/api/v2/documentation` es Swagger. La app móvil y el otro sitio usan `https://test1.fullvendor.com/api`.

## 5. Qué no hacer

- No crees otro sitio `api.ejemplo.com` ni cambies el `DocumentRoot` a `api/public`. En test1 el `DocumentRoot` es `public_html`.
- No dejes `APP_DEBUG=true` en el servidor. Los errores muestran rutas y SQL.
- No copies `api/.env` del entorno local. Genera `APP_KEY` allí y pon la base de ese servidor.
- Tras cambiar código PHP, si `opcache.validate_timestamps=0`, reinicia Apache para que tome los archivos nuevos.
