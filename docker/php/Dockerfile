FROM php:8.3-fpm-alpine3.18
WORKDIR /var/www/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk add curl bash && \
    curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony && \
    apk del curl
