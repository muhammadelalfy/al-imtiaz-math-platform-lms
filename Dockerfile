FROM php:8.3-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        libonig-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" mbstring pdo_pgsql pdo_sqlite zip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && corepack enable \
    && pnpm install --frozen-lockfile \
    && pnpm build \
    && rm -rf /root/.cache /tmp/*

ENV APP_ENV=production \
    APP_DEBUG=false

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT}"]
