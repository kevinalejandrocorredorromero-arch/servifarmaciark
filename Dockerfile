FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo_mysql \
    && a2enmod rewrite headers expires

COPY . /var/www/html/

# En el build desde el repositorio no existe config/config.php (gitignored):
# se genera a partir de la versión basada en variables de entorno.
RUN if [ ! -f config/config.php ] && [ -f config/config.env.php ]; then cp config/config.env.php config/config.php; fi

RUN mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/logs \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

WORKDIR /var/www/html
EXPOSE 80
