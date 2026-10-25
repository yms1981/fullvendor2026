#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> Exportando esquema y vistas..."
bash "${SCRIPT_DIR}/export_schema_from_db.sh"

echo "==> Exportando datos de referencia..."
bash "${SCRIPT_DIR}/export_reference_data.sh"

echo "==> Exportación completada."
