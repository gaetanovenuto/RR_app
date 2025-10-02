ARG XDEBUG_VERSION="xdebug"

FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
  imagemagick \
  libfreetype6-dev \
  libjpeg62-turbo-dev \
  libmagickwand-dev --no-install-recommends \
  libpng-dev \
  libzip-dev \
  unzip \
  && rm -rf /var/lib/apt/lists/* \
  && a2enmod rewrite \
  && docker-php-ext-install exif \
  && docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install -j$(nproc) gd \
  && docker-php-ext-install fileinfo \
  && docker-php-ext-install zip \
  && pecl install imagick && docker-php-ext-enable imagick \
  && docker-php-ext-install mysqli \
  && docker-php-ext-install pdo pdo_mysql
  