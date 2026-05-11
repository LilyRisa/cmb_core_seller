# Production `web` image: nginx serving public/ (built Vite assets included), proxying
# *.php to the `app` (php-fpm) container. Build context = ./app (see docker-compose.prod.yml).
#
# Assets are built ONCE — in app/docker/Dockerfile's `node` stage, which the runtime app
# image already exposes at /var/www/html/public/build (php-fpm needs manifest.json for @vite).
# We just copy that image's public/ here so the static files and the manifest stay in lock-step.
# (Dev uses plain nginx:1.27-alpine + bind mount + the `vite` HMR service — see docker-compose.override.yml.)

ARG APP_IMAGE=cmbcoreseller-app:latest
FROM ${APP_IMAGE} AS app

FROM nginx:1.27-alpine
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
