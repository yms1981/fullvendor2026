#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SEED_DIR="${ROOT_DIR}/database/sql/seed_data"
MANIFEST="${SEED_DIR}/manifest.json"

TABLES=(
  countries
  languages
  currency_formats
  currencybycountries
  states
  cities
  format_date
  format_numeric
)

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker no disponible." >&2
  exit 1
fi

MARIADB_CONTAINER="$(docker ps --format '{{.Names}}' 2>/dev/null | grep -E 'mariadb' | head -1 || true)"
if [[ -z "${MARIADB_CONTAINER}" ]]; then
  echo "No se encontró contenedor MariaDB." >&2
  exit 1
fi

mkdir -p "${SEED_DIR}"

echo "{" > "${MANIFEST}"
echo '    "version": 1,' >> "${MANIFEST}"
echo '    "apply_order": [' >> "${MANIFEST}"
for i in "${!TABLES[@]}"; do
  comma=","
  [[ $i -eq $((${#TABLES[@]} - 1)) ]] && comma=""
  echo "        \"${TABLES[$i]}\"${comma}" >> "${MANIFEST}"
done
echo '    ],' >> "${MANIFEST}"
echo '    "tables": {' >> "${MANIFEST}"

for i in "${!TABLES[@]}"; do
  table="${TABLES[$i]}"
  count="$(docker exec "${MARIADB_CONTAINER}" mariadb -uappfv -pfullvendorPWD appfv -N -e "SELECT COUNT(*) FROM \`${table}\`;" 2>/dev/null)"
  docker exec "${MARIADB_CONTAINER}" mariadb-dump -uappfv -pfullvendorPWD \
    --no-create-info --complete-insert --skip-add-locks --insert-ignore \
    appfv "${table}" 2>/dev/null > "${SEED_DIR}/${table}.sql"

  comma=","
  [[ $i -eq $((${#TABLES[@]} - 1)) ]] && comma=""
  cat >> "${MANIFEST}" <<EOF
        "${table}": {
            "file": "${table}.sql",
            "expected_rows": ${count}
        }${comma}
EOF
  echo "  exported ${table}: ${count} rows"
done

echo '    }' >> "${MANIFEST}"
echo '}' >> "${MANIFEST}"

echo "Manifest: ${MANIFEST}"
