FROM php:8.3-apache
RUN apt-get update && apt-get install -y \zip \unzip \git \libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql
WORKDIR /var/www/html 
#apache expects it as the default
COPY . .
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader
#from composer in composer folder
RUN chown -R www-data:www-data storage bootstrap/cache
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
EXPOSE 80