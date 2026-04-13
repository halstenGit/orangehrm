###############################################
# OrangeHRM - Halsten Production Build
# Multi-stage: Node (frontend) → PHP/Apache
# Designed for Railway deployment
###############################################

# ---- Stage 1: Build Vue frontend ----
FROM node:18-alpine AS frontend

WORKDIR /app/src/client
COPY src/client/ ./

# Output goes to --dest ../../web/dist → /app/web/dist
RUN yarn install --frozen-lockfile && yarn build

# ---- Stage 2: PHP/Apache production image ----
FROM php:8.3-apache-bookworm

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# System deps + PHP extensions
RUN set -ex; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg-dev \
        libpng-dev \
        libzip-dev \
        libldap2-dev \
        libicu-dev \
        unzip \
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-configure ldap \
        --with-libdir=lib/$(uname -m)-linux-gnu/ \
    ; \
    docker-php-ext-install -j "$(nproc)" \
        gd \
        opcache \
        intl \
        pdo_mysql \
        zip \
        ldap \
    ; \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
    ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); print so }' \
        | sort -u \
        | xargs -r dpkg-query -S \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/cache/apt/archives; \
    rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=60'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini; \
    { \
        echo 'upload_max_filesize=20M'; \
        echo 'post_max_size=20M'; \
        echo 'memory_limit=256M'; \
        echo 'max_execution_time=120'; \
    } > /usr/local/etc/php/conf.d/ohrm.ini; \
    a2enmod rewrite access_compat

WORKDIR /var/www/html

# Copy source code and fix Windows CRLF line endings
COPY . .
RUN find . -name "*.htaccess" -exec sed -i 's/\r$//' {} + && \
    find . -name "*.sh" -exec sed -i 's/\r$//' {} +

# Install PHP dependencies (production)
RUN composer install -d src --no-dev --no-interaction --optimize-autoloader

# Copy built frontend from stage 1
COPY --from=frontend /app/web/dist web/dist/

# Permissions
RUN chown www-data:www-data /var/www/html; \
    mkdir -p lib/confs lib/confs/cryptokeys src/cache src/log src/config; \
    chown -R www-data:www-data lib/confs src/cache src/log src/config; \
    chmod -R 775 lib/confs src/cache src/log src/config

# Entrypoint — strip CRLF in case of Windows line endings
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
