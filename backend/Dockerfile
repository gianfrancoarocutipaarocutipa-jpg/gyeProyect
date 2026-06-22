FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    nginx \
    supervisor \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pgsql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www

RUN chown -R www-data:www-data /var/www

COPY nginx.railway.conf /etc/nginx/sites-available/default
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
