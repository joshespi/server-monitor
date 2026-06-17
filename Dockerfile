FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev wget ca-certificates \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Internal CA certificates (Provo City School District SSL inspection proxy)
RUN wget --no-check-certificate -P /usr/local/share/ca-certificates/ \
    "https://ckr01.provo.edu/ckroot/ckroot.crt" \
    "https://internal-certs.provo.edu/pcsd_rootca.crt" \
    "https://internal-certs.provo.edu/pcsd_ca.crt" \
    && update-ca-certificates

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
