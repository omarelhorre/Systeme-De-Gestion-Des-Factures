FROM php:8.2-apache
RUN docker-php-ext-install mysqli && a2enmod rewrite 
# Mode démo : libfaketime permet de changer la date vue par PHP sans toucher à l'horloge du PC.
# Tant que le fichier de date n'existe pas (voir Traitement/demo.php), PHP voit la vraie date.
RUN apt-get update && apt-get install -y --no-install-recommends libfaketime && rm -rf /var/lib/apt/lists/*     && ln -s "$(find /usr/lib -name libfaketime.so.1 | head -1)" /usr/local/lib/libfaketime.so.1
# Pas de cache de code (opcache) : il se repère à l'heure et ne verrait plus les modifications du code
# quand la date de démo saute en avant puis revient. Sans effet visible pour une application de cette taille.
RUN echo "opcache.enable=0" > /usr/local/etc/php/conf.d/demo-opcache.ini
COPY apache.conf /etc/apache2/conf-enabled/override.conf
COPY . var/www/html