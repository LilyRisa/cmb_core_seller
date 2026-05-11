# Production `web` image: nginx serving public/ (Vite assets built here), proxying *.php
# to the `app` (php-fpm) container. Build context = ./app (see docker-compose.prod.yml).
# Self-contained (no dependency on the app image) so it builds cleanly anywhere — incl.
# Portainer git stacks. Vite content-hashes its output, so the manifest baked into the app
# image (app/docker/Dockerfile's node stage) and the static files here always line up.
# (Dev uses plain nginx:1.27-alpine + bind mount + the `vite` HMR service — see docker-compose.override.yml.)

# --- Stage 1: build the React/Vite assets -> public/build ---
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.ts tsconfig.json postcss.config.js tailwind.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# --- Stage 2: nginx + public/ (static + built assets) ---
FROM nginx:1.27-alpine
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=assets /app/public /var/www/html/public
