FROM php:8.3-fpm-alpine3.18
WORKDIR /var/www/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk add --update --no-cache rabbitmq-c-dev curl bash git libpng-dev postgresql-dev && \
    apk add --no-cache --update --virtual .phpize-deps $PHPIZE_DEPS && \
    pecl install -o -f amqp && \
    docker-php-ext-enable amqp && \
    docker-php-ext-install gd pdo pdo_pgsql && \
    apk del --purge .phpize-deps && \
    curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

