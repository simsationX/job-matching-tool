FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libicu-dev libonig-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    python3 python3-pip python3-venv \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl pdo_mysql zip gd

COPY docker/config/php/php.ini /usr/local/etc/php/

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Python Virtualenv
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Install sentence-transformers + torch
RUN pip install --no-cache-dir torch sentence-transformers numpy faiss-cpu \
    && pip cache purge

RUN python3 -c "\
from sentence_transformers import SentenceTransformer; \
SentenceTransformer('sentence-transformers/all-MiniLM-L6-v2', cache_folder='/opt/venv/transformers_cache').save('/opt/venv/transformers_cache/all-MiniLM-L6-v2')\
"

# Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html
