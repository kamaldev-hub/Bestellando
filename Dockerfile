# Use an official PHP image with Apache
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    # MySQL client for database operations from within the container (optional, but useful)
    default-mysql-client \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) gd \
 && docker-php-ext-install pdo pdo_mysql \
 && docker-php-ext-install zip \
 && docker-php-ext-install intl \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache
# Enable mod_rewrite for URL rewriting (e.g., for front controller pattern)
RUN a2enmod rewrite
# Update the Apache document root to point to the public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy application source code
# We will use a volume mount for development, but this ensures image can be built standalone.
COPY . /var/www/html

# Set permissions for storage and cache if needed by a framework (not strictly for vanilla PHP yet)
# RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache (example)

# Expose port 80
EXPOSE 80

# The default command is managed by the base php:apache image (starts Apache)
# CMD ["apache2-foreground"]
