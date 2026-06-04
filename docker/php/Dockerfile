FROM php:8.3-fpm

WORKDIR /var/www

ARG USER_NAME=app
ARG UID=1000
ARG GID=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip \
    libpng-dev libzip-dev libonig-dev libxml2-dev \
    libpq-dev libcurl4-gnutls-dev libxslt1-dev \
    libicu-dev pkg-config \
 && docker-php-ext-configure gd \
 && docker-php-ext-install \
    pdo pdo_pgsql pdo_mysql \
    zip xsl intl opcache \
    mbstring exif pcntl bcmath gd \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apt-get clean && rm -rf /var/lib/apt/lists/*


# Copy composer executable.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# create user and group
RUN groupadd -g ${GID} ${USER_NAME} \
    && useradd -u ${UID} -g ${USER_NAME} -m ${USER_NAME}

RUN chown -R ${USER_NAME}:${USER_NAME} /var/www




USER ${USER_NAME}



