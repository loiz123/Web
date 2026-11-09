FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring curl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/app.conf \
    && a2enconf app \
    && printf 'upload_max_filesize=5M\npost_max_size=55M\nmax_file_uploads=10\n' > /usr/local/etc/php/conf.d/uploads.ini

COPY . /var/www/html
COPY deploy/render-start.sh /usr/local/bin/render-start
RUN chmod +x /usr/local/bin/render-start \
    && mkdir -p /var/www/html/public/uploads/properties \
    && chown -R www-data:www-data /var/www/html/public/uploads

CMD ["render-start"]
