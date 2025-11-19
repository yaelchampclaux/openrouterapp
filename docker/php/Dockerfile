FROM php:8.3-cli
RUN useradd -ms /bin/bash you

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
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo gd iconv mysqli pdo_mysql zip intl \ 
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

USER you
WORKDIR /home/you
# Composer (composer.phar) installation
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

# Serveur php symfony
CMD [ "php", "-S", "0.0.0.0:8800", "-t", "openrouterapp/public" ]
