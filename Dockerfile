FROM php:8.3-fpm

RUN docker-php-ext-install mysqli pdo pdo_mysql calendar

COPY php.ini /usr/local/etc/php/php.ini
