FROM php:8.2-apache

# Extensão PDO para PostgreSQL (usada em app/Config/Database.php)
# libpq-dev é necessária para compilar o pdo_pgsql
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Aponta o DocumentRoot do Apache para a pasta /public do projeto
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!\${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!\${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY . /var/www/html

# O Render define a porta em tempo de execução via $PORT (padrão 10000).
RUN printf '#!/bin/sh\nset -e\nPORT="${PORT:-10000}"\nsed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf\nsed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf\nexec apache2-foreground\n' > /usr/local/bin/start-apache.sh \
    && chmod +x /usr/local/bin/start-apache.sh

EXPOSE 10000
CMD ["/usr/local/bin/start-apache.sh"]
