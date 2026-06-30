FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    bash \
    curl \
    mysql-client \
    libzip-dev \
    icu-dev \
    gettext-dev \
    oniguruma-dev \
    && docker-php-ext-install \
        mysqli \
        pdo \
        pdo_mysql \
        mbstring \
        gettext \
        intl \
        zip \
    && docker-php-ext-enable mysqli pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p logs files/temp \
    && chmod -R 775 logs files/temp

EXPOSE 8080

CMD ["bash", "start.sh"]
