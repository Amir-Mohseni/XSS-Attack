FROM --platform=linux/arm64 php:8.0-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

COPY src/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/ 