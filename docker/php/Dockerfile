FROM php:8.3-fpm-alpine3.18
WORKDIR /var/www/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk --update --no-cache add curl bash git libpng-dev postgresql-dev && \
    apk add --no-cache $PHPIZE_DEPS && \
    docker-php-ext-install gd pdo pdo_pgsql && \
    curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

