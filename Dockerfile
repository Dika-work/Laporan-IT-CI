# Gunakan base image PHP dengan Apache
FROM php:8.2-apache

# Install dependency yang dibutuhkan
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libicu-dev \
    && docker-php-ext-install intl pdo_mysql mysqli zip \
    && docker-php-ext-enable intl pdo_mysql mysqli

# Aktifkan modul Apache yang diperlukan
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy file proyek ke dalam container
COPY . /var/www/html

# Install dependencies menggunakan Composer
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist

# Buat konfigurasi Apache untuk CodeIgniter
RUN echo "<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>" > /etc/apache2/conf-available/codeigniter.conf

# Aktifkan konfigurasi Apache
RUN a2enconf codeigniter

# Perbarui konfigurasi Apache agar mengarah ke folder public/
RUN sed -i 's|/var/www/html|/var/www/html/public|' /etc/apache2/sites-enabled/000-default.conf

# Atur izin file dan folder
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/writable

# Jalankan Apache saat container berjalan
CMD ["apache2-foreground"]
