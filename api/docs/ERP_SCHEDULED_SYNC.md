# ERP Scheduled Sync (FullVendor ← fullvendorERP)

Pull-based sync: FullVendor reads from the ERP using the company token (`X-Api-Key`).

## Company profile

Fields on `companies`:

- `erp_enabled`
- `erp_company_code` (ERP `companies.code`)
- `erp_url` (base URL of fullvendorERP APIs, e.g. `http://localhost/fullvendorERP/public`)
- `erp_company_token` (ERP `companies.token`, hidden in API responses; `erp_has_token` flag instead)

UI: `/company/profile` → section **ERP** (before General). When the switch is on, the form asks for company code, API URL and company token.

Pull calls send `X-Api-Key` + `X-Company-Code`.

## Scheduled tasks UI

Settings → **Tareas programadas** → `/company/scheduledtasks`

Task types:

| task_type | ERP endpoint |
|-----------|--------------|
| `sync_customers` | `GET /api/v1/external/fv-sync/customers` |
| `sync_products` | `GET /api/v1/external/fv-sync/products` |
| `sync_categories` | `GET /api/v1/external/fv-sync/categories` |
| `sync_customer_balances` | `GET /api/v1/external/fv-sync/customer-balances` |
| `sync_product_inventories` | `GET /api/v1/external/fv-sync/product-inventories` |
| `sync_product_images` | `GET /api/v1/external/fv-sync/products` (usa `images[]` del listado) |

Frequencies: `manual`, `every_15_min`, `every_1_hour` … `every_6_hours`, `daily` (legacy `hourly` = 1 hour).

### Product images queue (`sync_product_images`)

Does **not** download everything in one HTTP burst:

1. Fetches the full ERP product list (`/external/fv-sync/products`, paginated) and reads each row’s `images[]`.
2. Matches SKUs to FullVendor products that are **active** (`status = 1`) and **without images**
   (`default_image_id` empty **and** no `product_images` rows). Inactive products are ignored.
3. Enqueues **one** absolute URL per product (ERP `is_default` or first valid `images[].url`).
   Before enqueue/drain, pending junk is pruned (inactive, already has photo, extra gallery rows).
   - FullVendor **does not** invent image URLs. The ERP (`MediaStorage::url`) must return:
     - remote: `https://…` as stored
     - local disk: `{APP_URL}/storage/products/…` (e.g. `http://localhost/fullvendorERP/public/storage/products/4/foto.jpg`)
   - Relative paths like `products/…` alone are ignored (fix the ERP API / `APP_URL`).
4. Downloads **one file at a time**. Arranque automático **sin tener el panel abierto**:
   - cron HTTP `/api/cron/schedule` cada minuto → procesa y, si queda cola, lanza `php artisan erp:process-image-queue` en background encadenado hasta vaciar
   - tras **Run** / encolar → `afterResponse` + spawn CLI
   - (opcional) poll del panel también drena si alguien lo tiene abierto
5. File log: `storage/logs/erp-image-queue-YYYY-MM-DD.log` (status per URL: queued / downloaded / failed / retry / skipped).
6. Live progress in MVC **Tareas programadas**: counters, percent bar, recent rows (poll while active), cron-health warning if no tick in 3 minutes.

Cron drain (every minute via `schedule:run`):

```bash
php artisan erp:process-image-queue --limit=40
```

Progress / drain API:

- `GET /api/v2/company/{company}/scheduled-tasks/image-queue?recent=40` (includes `cron_ok`, `cron_last_tick_at`)
- `POST /api/v2/company/{company}/scheduled-tasks/image-queue/process?limit=40`

MVC proxies: `GET /company/scheduledtasks/imageQueue`, `POST /company/scheduledtasks/processImageQueue`

### Execution history (logs)

Each run (manual **Run now** or cron) writes a row in `company_scheduled_task_logs` (status, message, duration, trigger).

UI: same page → **Historial de ejecuciones** (filters by task / status, refresh).

API:

- `GET /api/v2/company/{company}/scheduled-tasks/logs?task_type=&status=&per_page=&page=`

MVC proxy: `GET /company/scheduledtasks/logs`

## FullVendor API

- `GET /api/v2/company/{company}/scheduled-tasks`
- `GET /api/v2/company/{company}/scheduled-tasks/logs`
- `GET /api/v2/company/{company}/scheduled-tasks/image-queue`
- `POST /api/v2/company/{company}/scheduled-tasks/image-queue/process`
- `PUT /api/v2/company/{company}/scheduled-tasks/{taskType}`
- `POST /api/v2/company/{company}/scheduled-tasks/{taskType}/run`

## System scheduler (cron / Task Scheduler)

Laravel registers `erp:run-scheduled-tasks` and `erp:process-image-queue` **every minute** in `routes/console.php`.

Two ways to tick the scheduler (same idea as fullvendorERP):

### A) HTTP tick (recomendado en Linux / producción — igual que el ERP)

1. En `api/.env`:
```env
SCHEDULE_TOKEN=pon_un_secreto_largo
```
2. Cada minuto (crontab del servidor web):

```bash
chmod +x /var/www/mvc/api/scripts/cron/fv-schedule-http.sh

# Edita FV_SCHEDULE_URL si hace falta, o:
crontab -e
```

```cron
* * * * * FV_SCHEDULE_URL="https://web.fullvendor.com/api/cron/schedule" /var/www/mvc/api/scripts/cron/fv-schedule-http.sh >> /var/log/fv-schedule-http.log 2>&1
```

O directo:

```cron
* * * * * curl -fsS -G "https://web.fullvendor.com/api/cron/schedule" --data-urlencode "token=TU_SCHEDULE_TOKEN" --max-time 110 >> /var/log/fv-schedule-http.log 2>&1
```

Eso ejecuta `schedule:run` + drena hasta 40 fotos (PHP del Apache/FPM con GD).  
Sin este cron la cola se queda en `pending` (0 descargadas).

Probar:

```bash
curl -fsS -G "https://web.fullvendor.com/api/cron/schedule" --data-urlencode "token=TU_TOKEN"
# {"ok":true,"elapsed_ms":...,"output":"...erp:process-image-queue processed=..."}
```

Log: `storage/logs/scheduler-YYYY-MM-DD.log`

Tras desplegar el filtro activo/sin-foto: vuelve a pulsar **Run** en sync fotos (podará la cola inflada) o espera el próximo tick (también poda).

### B) CLI (`php artisan schedule:run`)

```bash
php artisan schedule:run
```

Scripts live under `api/scripts/cron/`.

### Linux (crontab CLI)

Installer:

```bash
sudo bash /var/www/mvc/api/scripts/cron/install-linux-cron.sh
```

Or manually:

```cron
* * * * * www-data /var/www/mvc/api/scripts/cron/fv-schedule-run.sh >> /var/log/fv-schedule.log 2>&1
```

HTTP alternative:

```cron
* * * * * /var/www/mvc/api/scripts/cron/fv-schedule-http.sh >> /var/log/fv-schedule-http.log 2>&1
```

That tick runs `schedule:run` **in the same PHP process** (no `runInBackground` / `onOneServer`), so the HTTP curl must wait until the batch finishes. Includes `erp:run-scheduled-tasks` and `erp:process-image-queue --limit=40`.

Adjust paths and `FV_PHP_BIN` / `APP_URL` if needed.

**Importante:** sin un cron que pegue cada minuto a `/api/cron/schedule` (o `php artisan schedule:run`), la cola de fotos se queda en `pending` tras el primer lote del “Ejecutar ahora”.

### Linux (systemd timer)

1. Edit `WorkingDirectory` / paths in `api/scripts/cron/fv-laravel-scheduler.service`.
2. Copy unit + timer to `/etc/systemd/system/`.
3. `systemctl daemon-reload && systemctl enable --now fv-laravel-scheduler.timer`

### Windows (XAMPP / Task Scheduler)

As Administrator:

```powershell
powershell -ExecutionPolicy Bypass -File H:\xampp\htdocs\mvc\api\scripts\cron\install-windows-task.ps1
```

Creates task **FullVendorLaravelScheduler** (every 1 minute → `fv-schedule-run.ps1`).

Manual check:

```powershell
cd H:\xampp\htdocs\mvc\api
C:\xampp\php\php.exe artisan schedule:run -v
C:\xampp\php\php.exe artisan erp:run-scheduled-tasks
```

Cron runs are logged with `trigger=cron`; UI **Run now** uses `trigger=manual`.

## Matching

- Customers: `tax_id`, else `email`
- Products: `sku`
- Categories: `category_name` (flat)
- Images: only FullVendor products without `default_image_id`

## ERP auth

Header: `X-Api-Key: {companies.token}` on fullvendorERP. Optional: `X-Company-Code`.

ERP URL in company profile may be any of:

- `http://localhost/fullvendorERP/public`
- `http://localhost/fullvendorERP/public/api`
- `http://localhost/fullvendorERP/public/api/v1`

FullVendor normalizes to `/api/v1/external/fv-sync/...`.

Healthcheck: `GET /api/v1/external/fv-sync/ping` (same headers). See also `fullvendorERP/docs/FV_SYNC_API.md`.
