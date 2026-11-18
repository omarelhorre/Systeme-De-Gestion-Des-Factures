FROM php:8.2-apache
RUN docker-php-ext-install mysqli && a2enmod rewrite 
RUN apt-get update && apt-get install -y --no-install-recommends libfaketime && rm -rf /var/lib/apt/lists/*     && ln -s "$(find /usr/lib -name libfaketime.so.1 | head -1)" /usr/local/lib/libfaketime.so.1
RUN echo "opcache.enable=0" > /usr/local/etc/php/conf.d/demo-opcache.ini
COPY apache.conf /etc/apache2/conf-enabled/override.conf
COPY . var/www/html