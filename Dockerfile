FROM php:8.0-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions for logs and uploads
RUN mkdir -p /var/www/html/logs /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port
EXPOSE 80

# The default Apache command will start automatically
