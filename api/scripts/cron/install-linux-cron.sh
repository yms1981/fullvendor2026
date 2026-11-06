#!/usr/bin/env bash
# Instala el cron de Linux para FullVendor (schedule:run cada minuto).
# Incluye: erp:run-scheduled-tasks + erp:process-image-queue (cola de fotos).
#
# Uso:
#   sudo bash /var/www/mvc/api/scripts/cron/install-linux-cron.sh
#   # o con usuario/php custom:
#   FV_CRON_USER=www-data FV_PHP_BIN=/usr/bin/php sudo -E bash install-linux-cron.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RUNNER="${SCRIPT_DIR}/fv-schedule-run.sh"
CRON_USER="${FV_CRON_USER:-www-data}"
MARKER="# FullVendor Laravel scheduler"

if [[ ! -x "${RUNNER}" ]]; then
  chmod +x "${RUNNER}" || true
fi

LINE="* * * * * ${RUNNER} >> /var/log/fv-schedule.log 2>&1"

if [[ "$(id -u)" -eq 0 ]]; then
  EXISTING="$(crontab -u "${CRON_USER}" -l 2>/dev/null || true)"
  if echo "${EXISTING}" | grep -Fq "${MARKER}"; then
    echo "Cron already installed for ${CRON_USER}."
    exit 0
  fi
  {
    echo "${EXISTING}"
    echo "${MARKER}"
    echo "${LINE}"
  } | crontab -u "${CRON_USER}" -
  echo "Installed crontab for ${CRON_USER}:"
  echo "  ${LINE}"
else
  EXISTING="$(crontab -l 2>/dev/null || true)"
  if echo "${EXISTING}" | grep -Fq "${MARKER}"; then
    echo "Cron already installed for current user."
    exit 0
  fi
  {
    echo "${EXISTING}"
    echo "${MARKER}"
    echo "${LINE}"
  } | crontab -
  echo "Installed crontab for $(whoami):"
  echo "  ${LINE}"
fi

echo
echo "Also available (systemd):"
echo "  sudo cp ${SCRIPT_DIR}/fv-laravel-scheduler.service /etc/systemd/system/"
echo "  sudo cp ${SCRIPT_DIR}/fv-laravel-scheduler.timer /etc/systemd/system/"
echo "  # edit WorkingDirectory / paths in the .service first"
echo "  sudo systemctl daemon-reload && sudo systemctl enable --now fv-laravel-scheduler.timer"
echo
echo "Manual checks:"
echo "  cd ${SCRIPT_DIR}/../.. && php artisan schedule:run -v"
echo "  php artisan erp:process-image-queue --limit=20"
echo "  tail -f storage/logs/erp-image-queue.log"
echo
echo "HTTP tick (hosting curl, like fullvendorERP):"
echo "  # set SCHEDULE_TOKEN in api/.env"
echo "  * * * * * ${SCRIPT_DIR}/fv-schedule-http.sh >> /var/log/fv-schedule-http.log 2>&1"
echo "  # or: curl -G \"\${APP_URL}/cron/schedule\" --data-urlencode \"token=\${SCHEDULE_TOKEN}\""
