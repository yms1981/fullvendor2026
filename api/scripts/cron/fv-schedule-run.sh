#!/usr/bin/env bash
# FullVendor Laravel scheduler tick (run every minute from cron).
# Install example:
#   * * * * * www-data /path/to/mvc/api/scripts/cron/fv-schedule-run.sh >> /var/log/fv-schedule.log 2>&1

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
PHP_BIN="${FV_PHP_BIN:-php}"

cd "${API_DIR}"
exec "${PHP_BIN}" artisan schedule:run --verbose --no-interaction
