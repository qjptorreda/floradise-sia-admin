FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy all project files to Apache web root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Set the document root to /var/www/html
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Expose port 80
EXPOSE 80
