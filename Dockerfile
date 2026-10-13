FROM php:8.5-cli AS vendor

RUN apt-get update \
 && apt-get install -y --no-install-recommends unzip \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist

FROM php:8.5-cli

WORKDIR /app

COPY . /app
COPY --from=vendor /app/vendor /app/vendor

ENTRYPOINT ["php", "bin/keycheat"]
CMD ["help"]
