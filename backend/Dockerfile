FROM php:8.3-apache
RUN apt-get update && apt-get install -y \zip \unzip \git \libpq-dev
#install laravel and postgress dependecines in \zip ... are directories
RUN docker-php-ext-install pdo pdo_pgsql 
#install php extensions to connect with postgres sql
WORKDIR /var/www/html 
#apache expects it as the default
COPY . .
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader
#from composer in composer folder (install all composer dependencies)
RUN chown -R www-data:www-data storage bootstrap/cache 
#we make www-data from group www-data  the owner of storage and bootstrap/cache (they are both folders to STORE sessions des fichiers de cours etc..., pour accelerer l acces au routes donc on a besoin de cache )
RUN a2enmod rewrite
#pour que appache peut rederiges les bonnes routes car le defaut c est index.php
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
#substituer tout come var/www/html par /var/www/html/public dans les fichiers /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
#when looking for files go do var/ww/
EXPOSE 80