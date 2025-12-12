FROM php:8.3-cli

# Créer l'utilisateur
RUN useradd -ms /bin/bash you

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    vim \
    git \
    zip \
    unzip \
    supervisor \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        iconv \
        zip \
        intl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Configuration PHP optimisée pour Symfony
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/symfony.ini \
    && echo "opcache.enable = 1" >> /usr/local/etc/php/conf.d/symfony.ini \
    && echo "opcache.memory_consumption = 256" >> /usr/local/etc/php/conf.d/symfony.ini \
    && echo "opcache.max_accelerated_files = 20000" >> /usr/local/etc/php/conf.d/symfony.ini \
    && echo "opcache.validate_timestamps = 1" >> /usr/local/etc/php/conf.d/symfony.ini \
    && echo "realpath_cache_size = 4096K" >> /usr/local/etc/php/conf.d/symfony.ini \
    && echo "realpath_cache_ttl = 600" >> /usr/local/etc/php/conf.d/symfony.ini

# Installation de Composer (en tant que root, dans /usr/local/bin)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Changer vers l'utilisateur you
USER you
WORKDIR /home/you

# Serveur php symfony
CMD [ "php", "-S", "0.0.0.0:8800", "-t", "ora/public" ]