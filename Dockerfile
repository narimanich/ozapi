FROM php:8.3-apache

# Установка необходимых расширений
RUN apt-get update && \
    apt-get install -y git unzip libpng-dev libonig-dev libxml2-dev && \
    rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd opcache

# Включаем модули Apache
RUN a2enmod rewrite headers

# Копируем проект
COPY . /var/www/html
WORKDIR /var/www/html

# Устанавливаем Composer и зависимости
RUN curl -sS https://getcomposer.org/installer  | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer install --no-dev --optimize-autoloader

# Права доступа
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && \
    find /var/www/html -type f -exec chmod 664 {} \; && \
    find /var/www/html -type d -exec chmod 775 {} \;

# Настройка Apache
RUN echo "<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public/\n\
    ServerAdmin admin@example.com\n\
    <Directory /var/www/html/public/>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        RewriteEngine on\n\
        RewriteBase /\n\
        RewriteCond %{REQUEST_FILENAME} !-f\n\
        RewriteCond %{REQUEST_FILENAME} !-d\n\
        RewriteRule ^ index.php [QSA,L]\n\
    </Directory>\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

EXPOSE 80