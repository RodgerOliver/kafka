FROM php:8.2-fpm

RUN apt-get update --fix-missing \
    && apt-get install -y curl wget zip unzip git iputils-ping \
    && rm -rf /var/lib/apt/lists/*

# DEPENDENCIES
RUN apt-get update --fix-missing \
    && apt-get install -y  python3 \
    && rm -rf /var/lib/apt/lists/*

RUN cp /usr/bin/python3 /usr/bin/python

# SOAP
RUN apt-get update && \
    apt-get install -y libxml2-dev
RUN docker-php-ext-install soap

# KAFKA
RUN apt-get install librdkafka-dev -y
RUN pecl install rdkafka \
    && docker-php-ext-enable rdkafka

COPY php.ini.example /usr/local/etc/php/php.ini

# Set working directory and user
WORKDIR /usr/share/nginx/www

# create a new linux user group called 'developer' with an arbitrary group id of '1001'
RUN groupadd -g 1000 developer

# create a new user called developer and add it to this group
RUN useradd -u 1000 -g developer developer

# change the owner and group of the current working directory to developer
COPY --chown=developer:developer . /usr/share/nginx/www

# run all subsequent processes as this user
USER developer
