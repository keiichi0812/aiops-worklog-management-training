FROM php:8.4-fpm-bookworm

# Containers run as the host user, whose home directory does not exist in the image
# (Composer / npm caches and the Tinker config are written under $HOME).
ENV HOME=/tmp

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libicu-dev \
    && docker-php-ext-install -j"$(nproc)" intl pcntl pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# The Wayfinder Vite plugin runs `php artisan` during builds, so PHP and Node must share a container.
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
