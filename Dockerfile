# Gunakan image PHP dengan Apache
FROM php:8.1-apache

# Install ekstensi yang dibutuhkan
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy semua file project ke dalam container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Atur permission
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 untuk server
EXPOSE 80

# Jalankan Apache di background
CMD ["apache2-foreground"]
