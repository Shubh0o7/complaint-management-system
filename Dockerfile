FROM php:8.3-apache

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends mariadb-server mariadb-client \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/
COPY docker-entrypoint-render.sh /usr/local/bin/docker-entrypoint-render.sh

RUN chmod +x /usr/local/bin/docker-entrypoint-render.sh \
    && mkdir -p /var/www/html/uploads /var/lib/mysql \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads \
    && chown -R mysql:mysql /var/lib/mysql

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/docker-entrypoint-render.sh"]
