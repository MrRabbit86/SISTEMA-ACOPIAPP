# syntax=docker/dockerfile:1

FROM php:8.4-apache
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    gnupg \
    git \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        gd \
        zip \
        xml \
        bcmath \
        intl \
        opcache \
    && a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && npm ci --no-audit --no-fund \
    && npm run build \
    && rm -rf node_modules /root/.npm

RUN { \
        echo "upload_max_filesize=8M"; \
        echo "post_max_size=12M"; \
        echo "memory_limit=256M"; \
    } > /usr/local/etc/php/conf.d/acopiapp.ini

RUN printf '<VirtualHost *:${PORT}>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
