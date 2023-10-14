FROM php:8.2-fpm-alpine

RUN apk update \
    && apk add --no-cache icu icu-dev autoconf gcc g++ linux-headers make bash \
    && docker-php-ext-install intl opcache pdo pdo_mysql bcmath \
    && pecl install apcu xdebug \
    && docker-php-ext-enable apcu xdebug \
    && apk del icu-dev autoconf gcc g++ linux-headers make \
    && php -v \
    && php -m

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer --version

RUN curl -sS https://get.symfony.com/cli/installer | bash -s -- --install-dir=/usr/local/bin \
    && symfony -V

WORKDIR /app
