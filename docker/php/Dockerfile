# PHP 8.5 + Apache + PDO_FIREBIRD (fbclient 4.0 do Debian trixie, compatível com servidor Firebird 5).
FROM php:8.5.11-apache-trixie

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends firebird-dev libfbclient2 unzip; \
    docker-php-ext-install -j"$(nproc)" pdo_firebird; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.10 /usr/bin/composer /usr/local/bin/composer

RUN set -eux; \
    a2enmod rewrite headers deflate; \
    mkdir -p /var/lib/fiscaliza/sessions /var/log/fiscaliza; \
    chown -R www-data:www-data /var/lib/fiscaliza /var/log/fiscaliza; \
    chmod 700 /var/lib/fiscaliza/sessions

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-fiscaliza.ini
COPY docker/php/apache-site.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/portal-stub /var/www/portal-stub

WORKDIR /app
