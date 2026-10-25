#!/bin/sh
# Construye las imágenes MVC + API en local y las exporta a .tar.gz
# para copiar al servidor (docker load). No hace push ni modifica compile.sh.
set -e

ROOT="$(CDPATH= cd -- "$(dirname "$0")" && pwd)"
cd "$ROOT"

APP_VERSION="$(git describe --tags --always 2>/dev/null || git rev-parse --short HEAD 2>/dev/null || echo dev)"
OUT_DIR="${1:-$ROOT/dist/docker-images}"
FRONTEND_TAG="${FRONTEND_TAG:-ghcr.io/fullvendor/frontend:latest}"
BACKEND_TAG="${BACKEND_TAG:-ghcr.io/fullvendor/backend:latest}"

mkdir -p "$OUT_DIR"

echo "==> Versión APP: $APP_VERSION"
echo "==> Salida: $OUT_DIR"
echo ""

echo "==> [1/4] Build MVC (frontend): $FRONTEND_TAG"
docker build \
  -f docker/test/mvc/Dockerfile \
  -t "$FRONTEND_TAG" \
  --build-arg APP_VERSION="$APP_VERSION" \
  .

echo ""
echo "==> [2/4] Build API (backend): $BACKEND_TAG"
docker build \
  -f docker/test/api/Dockerfile \
  -t "$BACKEND_TAG" \
  .

FRONTEND_ARCHIVE="$OUT_DIR/frontend.tar.gz"
BACKEND_ARCHIVE="$OUT_DIR/backend.tar.gz"

echo ""
echo "==> [3/4] Export frontend -> $FRONTEND_ARCHIVE"
docker save "$FRONTEND_TAG" | gzip > "$FRONTEND_ARCHIVE"

echo ""
echo "==> [4/4] Export backend -> $BACKEND_ARCHIVE"
docker save "$BACKEND_TAG" | gzip > "$BACKEND_ARCHIVE"

echo ""
echo "Listo."
echo "  - $FRONTEND_ARCHIVE"
echo "  - $BACKEND_ARCHIVE"
echo ""
echo "En el servidor:"
echo "  docker load -i frontend.tar.gz"
echo "  docker load -i backend.tar.gz"
echo "  cd docker/test && docker compose up -d --no-build mvc api"
