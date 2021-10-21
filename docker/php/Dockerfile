FROM php:7.4-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY --from=composer /usr/bin/composer /usr/bin/composer