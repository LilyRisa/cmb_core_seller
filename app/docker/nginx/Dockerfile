# Production `web` image: nginx serving public/ with the Vite-built assets baked in.
# Build context = ./app (see docker-compose.prod.yml). nginx proxies *.php to app:9000.
# (Dev uses plain nginx:1.27-alpine + bind mount + the `vite` HMR service instead — see
# docker-compose.override.yml.)

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
# The whole public/ from stage 1 (index.php, .htaccess, robots.txt, favicon.ico) plus the
# freshly built public/build/. php-fpm on the `app` container has the same /var/www/html
# layout, so SCRIPT_FILENAME paths line up.
COPY --from=assets /app/public /var/www/html/public
