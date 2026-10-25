docker build \
  -f docker/test/mvc/Dockerfile \
  -t ghcr.io/fullvendor/frontend:latest \
  --build-arg APP_VERSION="$(git describe --tags)" \.

docker push ghcr.io/fullvendor/frontend:latest

docker build \
  -f docker/test/api/Dockerfile \
  -t ghcr.io/fullvendor/backend:latest .


docker push ghcr.io/fullvendor/backend:latest
