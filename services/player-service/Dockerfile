FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY public/ /var/www/html/
COPY src/ /var/www/src/

ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Configure Apache to use the new document root
RUN sed -ri -e 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/!g' /etc/apache2/apache2.conf

# Allow overriding to access src files
RUN echo "php_value include_path '.:/var/www/src'" > /var/www/html/.htaccess

EXPOSE 80
CMD ["apache2-foreground"]
