ARG VERSION

FROM php:${VERSION}-fpm-alpine AS dev

RUN apk update && \
    apk add --no-cache --virtual dev-deps git autoconf gcc g++ make && \
    apk add --no-cache zlib-dev libzip-dev linux-headers openssl acl postgresql-dev libxslt-dev pcre-dev rabbitmq-c-dev

RUN pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    docker-php-ext-install pdo_pgsql intl zip xsl

RUN echo "Extensions install - start" \
    # Sockets
    && docker-php-ext-install sockets \
    # Amqp
    && apk add --no-cache --update rabbitmq-c-dev \
    && apk add --no-cache --update --virtual .phpize-deps $PHPIZE_DEPS \
    && pecl install -o -f amqp \
    && docker-php-ext-enable amqp \
    && pecl install redis \
    && docker-php-ext-enable redis.so \
    && apk del .phpize-deps \
    #
    && echo "Extensions install - finish"

COPY ./docker/php-fpm/default.ini /usr/local/etc/php/conf.d/default.ini

WORKDIR /app

RUN wget https://getcomposer.org/installer && \
    php installer --install-dir=/usr/local/bin/ --filename=composer && \
    rm installer

FROM dev as test

WORKDIR /app

COPY . .

RUN XDEBUG_MODE=off composer install --no-interaction --optimize-autoloader

RUN chown -R www-data:www-data .

FROM test AS prod

ENV APP_ENV=prod
WORKDIR /app

RUN XDEBUG_MODE=off composer install --no-dev --no-interaction --optimize-autoloader

RUN apk del dev-deps && \
    rm /usr/local/bin/composer