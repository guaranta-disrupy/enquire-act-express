FROM docker.io/library/node:20 AS nodebuilder

WORKDIR /var/www

COPY . .

# RUN npm ci

# # RUN mkdir -p vendor/tightenco && \
# #     cd vendor/tightenco && \
# #     git clone --depth 1 --branch v1.8.0 https://github.com/tighten/ziggy.git
# RUN php artisan optimize:clear
# RUN php artisan view:clear
# RUN composer dump-autoload -o
RUN npm install

RUN npm run build

FROM devasdf/phpswoole:8.3

COPY . .

COPY --from=nodebuilder /var/www/public/build /app/public/build

RUN composer install

RUN sh -c "mkdir -p storage/framework/cache storage/framework/views storage/framework/sessions storage/framework/testing"

RUN php artisan storage:link
    
COPY entrypoint.sh /app/entrypoint.sh
RUN chmod +x /app/entrypoint.sh

ENTRYPOINT ["php", "artisan", "octane:start", "--host=0.0.0.0", "--port=9000", "--server=swoole", "--workers=5", "--max-requests=10000"]
