#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
VIEWS_DIR="${ROOT_DIR}/database/sql/views"
SCHEMA_FILE="${ROOT_DIR}/database/sql/appfv_full_schema.sql"

mkdir -p "${VIEWS_DIR}"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker no disponible." >&2
  exit 1
fi

MARIADB_CONTAINER="$(docker ps --format '{{.Names}}' 2>/dev/null | grep -E 'mariadb' | head -1 || true)"
if [[ -z "${MARIADB_CONTAINER}" ]]; then
  echo "No se encontró contenedor MariaDB." >&2
  exit 1
fi

docker exec "${MARIADB_CONTAINER}" mariadb-dump -uappfv -pfullvendorPWD --force --no-data appfv 2>/dev/null > "${SCHEMA_FILE}"

mapfile -t VIEWS < <(docker exec "${MARIADB_CONTAINER}" mariadb -uappfv -pfullvendorPWD appfv -N -e \
  "SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA='appfv' ORDER BY 1;" 2>/dev/null)

for view in "${VIEWS[@]}"; do
  [[ -z "${view}" ]] && continue

  create_sql="$(docker exec "${MARIADB_CONTAINER}" mariadb -uappfv -pfullvendorPWD appfv -N -B -e \
    "SHOW CREATE VIEW \`${view}\`" 2>/dev/null | cut -f2)"

  if [[ -z "${create_sql}" ]]; then
    echo "  omitida (sin definición): ${view}" >&2
    continue
  fi

  create_sql="$(echo "${create_sql}" | sed -E 's/CREATE ALGORITHM=[^ ]+ DEFINER=[^ ]+ SQL SECURITY [^ ]+ VIEW/CREATE OR REPLACE VIEW/i')"
  create_sql="$(echo "${create_sql}" | sed -E 's/CREATE VIEW/CREATE OR REPLACE VIEW/i')"

  printf '%s;\n' "${create_sql}" > "${VIEWS_DIR}/${view}.sql"
  echo "  exported view: ${view}"
done

echo "Esquema exportado: ${SCHEMA_FILE}"
echo "Vistas exportadas: ${#VIEWS[@]} en ${VIEWS_DIR}"
