#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
API_DIR="${ROOT_DIR}/api"

run_artisan() {
  cd "${API_DIR}"
  php artisan schema:sync "$@"
}

if command -v php >/dev/null 2>&1; then
  run_artisan "$@"
  exit 0
fi

if command -v docker >/dev/null 2>&1; then
  CONTAINER="$(docker ps --format '{{.Names}}' 2>/dev/null | grep -E 'laravel\.test|fullvendor.*api|fullvendor.*laravel' | head -1 || true)"
  if [[ -n "${CONTAINER}" ]]; then
    exec docker exec "${CONTAINER}" php artisan schema:sync "$@"
  fi
fi

echo "Error: PHP no está instalado y no se encontró un contenedor Docker de la API." >&2
echo "Ejecute manualmente: cd api && php artisan schema:sync --dry-run" >&2
exit 1
