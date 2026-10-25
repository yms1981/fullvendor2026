#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
API_DIR="${ROOT_DIR}/api"

run_artisan() {
  cd "${API_DIR}"
  php artisan "$@"
}

if command -v php >/dev/null 2>&1; then
  run_artisan schema:export "$@"
  exit $?
fi

if command -v docker >/dev/null 2>&1; then
  CONTAINER="$(docker ps --format '{{.Names}}' 2>/dev/null | grep -E 'laravel\.test|fullvendor.*api|fullvendor.*laravel' | head -1 || true)"
  if [[ -n "${CONTAINER}" ]]; then
    exec docker exec "${CONTAINER}" php artisan schema:export "$@"
  fi
fi

echo "Error: no se pudo ejecutar schema:export." >&2
exit 1
