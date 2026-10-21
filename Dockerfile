FROM php:8.2-apache
RUN docker-php-ext-install mysqli && a2enmod rewrite
COPY apache.conf /etc/apache2/conf-enabled/override.conf
COPY . var/www/html