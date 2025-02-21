FROM --platform=linux/arm64 php:8.0-apache

COPY src/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

# Create logs directory and set permissions
RUN mkdir -p /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html/logs && \
    chmod -R 755 /var/www/html/logs 