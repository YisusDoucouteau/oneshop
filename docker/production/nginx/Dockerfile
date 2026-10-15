FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build


FROM nginx:1.27-alpine

COPY docker/production/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/public
COPY --from=assets /app/public/build /var/www/public/build

RUN rm -rf /var/www/public/storage \
    && ln -s /var/www/storage/app/public /var/www/public/storage

