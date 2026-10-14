FROM php:8.2-cli-alpine

# Tools used by Composer to download dependencies
RUN apk add --no-cache git unzip

# Composer binary only, without its PHP runtime
COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

WORKDIR /app

ENV COMPOSER_HOME=/tmp/composer
