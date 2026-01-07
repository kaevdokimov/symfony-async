# Базовый образ
FROM php:8.5-fpm-alpine3.23

# Рабочая директория
WORKDIR /var/www/

# Копируем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка зависимостей
RUN apk add --no-cache --update \
    bash git libpng-dev postgresql-dev rabbitmq-c-dev && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql && \
    pecl install -o -f amqp redis && \
    docker-php-ext-enable amqp redis && \
    apk del --purge .build-deps && \
    rm -rf /var/cache/apk/* /tmp/*

# Установка Symfony CLI через PHAR
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Настройка Composer
RUN mkdir -p /root/.cache/composer && \
    chmod -R ugo+rwX /root/.cache/composer && \
    composer global config --no-interaction allow-plugins.symfony/flex true && \
    composer global require symfony/flex --quiet --no-interaction || true

ENV COMPOSER_CACHE_DIR=/root/.cache/composer \
    COMPOSER_ALLOW_SUPERUSER=1

# Копирование кода приложения
COPY . .

# Установка зависимостей
RUN if [ "$APP_ENV" = "prod" ]; then \
        composer install --no-dev --optimize-autoloader --classmap-authoritative --no-scripts; \
    else \
        composer install --prefer-dist --no-progress --no-scripts; \
    fi && \
    composer clear-cache

