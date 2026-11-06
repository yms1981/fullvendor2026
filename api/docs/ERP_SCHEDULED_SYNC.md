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
| `sync_product_images` | `GET /api/v1/external/fv-sync/products/{sku}/images` |

Frequencies: `manual`, `every_15_min`, `every_1_hour` … `every_6_hours`, `daily` (legacy `hourly` = 1 hour).

### Product images queue (`sync_product_images`)

Does **not** download everything in one HTTP burst:

1. Lists ERP images for FullVendor products without `default_image_id`.
2. Enqueues each URL in `erp_product_image_queue` (`pending`).
3. Downloads **one file at a time**; on failure marks the row and continues.
4. File log: `storage/logs/erp-image-queue-YYYY-MM-DD.log` (status per URL: queued / downloaded / failed / retry / skipped).
5. Live progress in MVC **Tareas programadas**: counters, percent bar, recent rows (poll every 5s while active).

Cron drain (every minute via `schedule:run`):

```bash
php artisan erp:process-image-queue --limit=40
```

Progress API:

- `GET /api/v2/company/{company}/scheduled-tasks/image-queue?recent=40`

MVC proxy: `GET /company/scheduledtasks/imageQueue`

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
- `PUT /api/v2/company/{company}/scheduled-tasks/{taskType}`
- `POST /api/v2/company/{company}/scheduled-tasks/{taskType}/run`

## System scheduler (cron / Task Scheduler)

Laravel registers `erp:run-scheduled-tasks` **every minute** in `routes/console.php`. The OS must call:

```bash
php artisan schedule:run
```

every minute. Scripts live under `api/scripts/cron/`.

### Linux (crontab)

Installer:

```bash
sudo bash /var/www/mvc/api/scripts/cron/install-linux-cron.sh
```

Or manually:

```cron
* * * * * www-data /var/www/mvc/api/scripts/cron/fv-schedule-run.sh >> /var/log/fv-schedule.log 2>&1
```

That tick runs `schedule:run`, which includes `erp:run-scheduled-tasks` and `erp:process-image-queue`.

Adjust paths and `FV_PHP_BIN` if needed.

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
