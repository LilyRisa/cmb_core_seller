# Stage 1: build frontend assets
FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json vite.config.* ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

# Stage 2: nginx + public/
FROM nginx:1.27-alpine
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=assets /app/public/build /var/www/html/public/build