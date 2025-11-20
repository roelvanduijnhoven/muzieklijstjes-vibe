ARG PHP_IMAGE="8.2-apache"
FROM php:${PHP_IMAGE} AS runtime

ARG UID
ENV APACHE_DOCUMENT_ROOT=/code/public

# Install and configure Composer.
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
RUN composer self-update 2.6.6 # Later versions don't allow root

RUN apt-get update && apt-get install -y libxml2 zlib1g-dev git unzip libpng-dev libjpeg-dev libfreetype6-dev

# Make sure we can run this container locally as well without leading to permission problems.
RUN useradd docker_user --user-group --create-home --uid ${UID:-1000}

# Change root directory
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable rewrite engine
RUN a2enmod rewrite
RUN a2enmod env

# Configure Apache to pass environment variables
# TODO#Roel Ideally we would expose everything, but that seems impossible with Apache?
RUN echo 'PassEnv QDRANT_HOST PEXELS_API_KEY UNSPLASH_API_KEY IMAGE_UTILS_HOST DATABASE_URL GOOGLE_APPLICATION_CREDENTIALS_JSON SENTRY_DSN APP_SECRET' >> /etc/apache2/apache2.conf

# Configure PHP
COPY resources/docker/php.ini $PHP_INI_DIR/php.ini

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql gd

WORKDIR "/code"

FROM runtime AS packaged

COPY composer.json composer.json
COPY composer.lock composer.lock
COPY run run
COPY symfony symfony
COPY symfony symfony
COPY symfony.lock symfony.lock

COPY bin bin
COPY config config
COPY public public
COPY resources resources
COPY src src
COPY templates templates

# Make sure your cache directory is created if it doesn’t exist
RUN mkdir -p var/cache
RUN chown -R www-data:www-data var/cache \
    && chmod -R 775 var/cache

RUN mkdir -p var/log
RUN chown -R www-data:www-data var/log \
    && chmod -R 775 var/log

# TODO This is a sucky way to do it, as it will ignore the .env set. But yeah, it's the best we got now!
#   Likely ditch Apache2 for nginx, which I understand.
RUN echo 'SetEnv APP_ENV prod' > /etc/apache2/conf-enabled/environment.conf

# By having an empty .env file, we will stop Symfony from reading from the .env.dist file.
RUN touch .env

RUN --mount=type=cache,target=/root/.composer composer install --no-scripts --no-dev --prefer-dist --optimize-autoloader

EXPOSE 80
