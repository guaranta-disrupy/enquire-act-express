FROM devasdf/phpswoole:8.4-node

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-scripts && \
    composer dump-autoload --optimize

RUN npm ci && \
    npm run build && \
    rm -rf node_modules package*.json .git* README* tests

RUN bash -c "mkdir -p storage/framework/{cache,views,sessions,testing}"

COPY entrypoint.sh /app-entrypoint.sh
RUN chmod +x /app-entrypoint.sh

ENTRYPOINT ["/app-entrypoint.sh"]
