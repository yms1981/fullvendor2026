#!/usr/bin/env sh
# Construye imágenes para desplegar en servidor (Webmin/Docker) sin depender de Sail en el servidor.
# Uso: desde la raíz del repo:  ./scripts/build-imagenes-servidor.sh

set -e
ROOT=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
cd "$ROOT"

echo "=== 1/2 Imagen MVC (PHP 7.4 Apache, prod) ==="
docker build -f docker/Dockerfile --target prod -t fullvendor/mvc-php:7.4-prod ./docker

echo "=== 2/2 Imagen API Laravel (PHP 8.3 Apache) ==="
docker build -f api/docker/Dockerfile -t fullvendor/api:8.3 ./api

echo ""
echo "Listo. Imágenes locales:"
echo "  fullvendor/mvc-php:7.4-prod"
echo "  fullvendor/api:8.3"
echo ""
echo "Subir al registry (ejemplo):"
echo "  docker tag fullvendor/api:8.3 REGISTRY/tu-usuario/fullvendor-api:8.3"
echo "  docker tag fullvendor/mvc-php:7.4-prod REGISTRY/tu-usuario/fullvendor-mvc:7.4"
echo "  docker push REGISTRY/tu-usuario/fullvendor-api:8.3"
echo "  docker push REGISTRY/tu-usuario/fullvendor-mvc:7.4"
echo ""
echo "En el servidor (carpeta api): docker compose -f compose.servidor.yaml up -d"
echo "En el servidor (MVC):       docker compose -f compose.prod.yaml up -d"
