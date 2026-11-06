#!/usr/bin/env bash
# Tick del scheduler FullVendor por HTTP (igual que fullvendorERP /cron/schedule).
# Útil en hosting donde solo hay cron URL / curl, sin CLI PHP.
#
# Variables:
#   FV_SCHEDULE_URL   (default: APP_URL/cron/schedule leído de .env si existe)
#   FV_SCHEDULE_TOKEN (o SCHEDULE_TOKEN en el entorno / .env de la API)
#
# Crontab ejemplo:
#   * * * * * /var/www/mvc/api/scripts/cron/fv-schedule-http.sh >> /var/log/fv-schedule-http.log 2>&1

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
ENV_FILE="${API_ROOT}/.env"

read_env() {
  local key="$1"
  if [[ -f "${ENV_FILE}" ]]; then
    grep -E "^${key}=" "${ENV_FILE}" | tail -n1 | cut -d= -f2- | tr -d '\r' | sed 's/^"//;s/"$//'
  fi
}

TOKEN="${FV_SCHEDULE_TOKEN:-${SCHEDULE_TOKEN:-$(read_env SCHEDULE_TOKEN)}}"
APP_URL="${FV_APP_URL:-$(read_env APP_URL)}"
APP_URL="${APP_URL%/}"
# Por defecto /api/cron/schedule (XAMPP: http://127.0.0.1/api/...).
URL="${FV_SCHEDULE_URL:-${APP_URL}/api/cron/schedule}"

if [[ -z "${TOKEN}" ]]; then
  echo "SCHEDULE_TOKEN vacío. Defínelo en api/.env" >&2
  exit 1
fi

curl -fsS -G "${URL}" --data-urlencode "token=${TOKEN}" \
  -H "Accept: application/json" \
  --max-time 110
echo
