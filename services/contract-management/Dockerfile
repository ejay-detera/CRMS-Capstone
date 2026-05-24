FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    curl unzip git libpng-dev libonig-dev libxml2-dev libssl-dev pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo pdo_mysql mbstring

RUN echo "upload_max_filesize = 12M" > /usr/local/etc/php/conf.d/uploads-limit.ini \
    && echo "post_max_size = 15M" >> /usr/local/etc/php/conf.d/uploads-limit.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN rm -f composer.lock && composer update -W --no-scripts --prefer-dist

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]