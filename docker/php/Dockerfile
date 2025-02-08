FROM php:8.4-fpm-alpine3.21
WORKDIR /var/www/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk add --update rabbitmq-c-dev curl bash git libpng-dev postgresql-dev && \
    apk add --update --virtual .phpize-deps $PHPIZE_DEPS && \
    pecl install -o -f amqp redis && \
    docker-php-ext-enable amqp redis && \
    docker-php-ext-install gd pdo pdo_pgsql && \
    apk del --purge .phpize-deps && \
    curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony
