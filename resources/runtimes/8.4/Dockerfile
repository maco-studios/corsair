FROM php:8.4-fpm-bookworm
LABEL maintainer="Marcos \"Marcão\" Aurelio"

ARG WWWGROUP=1000
ARG WWWUSER=1000
ENV SUPERVISOR_PHP_USER=corsair
ENV XDEBUG_MODE=off

RUN groupadd --force -g "$WWWGROUP" corsair \
  && useradd -ms /bin/bash --no-user-group -g "$WWWGROUP" -u "$WWWUSER" corsair

RUN mkdir -p /etc/nginx/html /var/www/html /sock /var/log/nginx /var/lib/nginx /var/www/html/var/profile \
  && chown -R corsair:corsair /etc/nginx /var/www /usr/local/etc/php/conf.d /sock /var/log/nginx /var/lib/nginx

RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash -

RUN apt-get update && apt-get install -y \
    cron \
    default-mysql-client \
    git \
    gnupg \
    gosu \
    gzip \
    libbz2-dev \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libmagickwand-dev \
    libmcrypt-dev \
    libonig-dev \
    libpng-dev \
    libsodium-dev \
    libssh2-1-dev \
    libwebp-dev \
    libxslt1-dev \
    libzip-dev \
    lsof \
    mailutils \
    msmtp \
    nginx \
    nodejs \
    procps \
    rsync \
    strace \
    supervisor \
    vim \
    zip \
    zlib1g-dev \
  && rm -rf /var/lib/apt/lists/*

RUN pecl channel-update pecl.php.net && pecl install \
    redis-6.3.0 \
    ssh2-1.5.0 \
    swoole-6.2.1 \
    imagick-3.8.1 \
    xdebug-3.5.1 \
  && pecl clear-cache \
  && rm -rf /tmp/pear

RUN docker-php-ext-configure \
    gd --with-freetype --with-jpeg --with-webp \
  && docker-php-ext-install \
    bcmath \
    bz2 \
    calendar \
    exif \
    ftp \
    gd \
    gettext \
    intl \
    mbstring \
    mysqli \
    opcache \
    pcntl \
    pdo_mysql \
    soap \
    sockets \
    sodium \
    sysvmsg \
    sysvsem \
    sysvshm \
    xsl \
    zip \
  && docker-php-ext-enable \
    imagick \
    redis \
    ssh2 \
    xdebug \
    swoole

RUN curl -sS https://getcomposer.org/installer | \
  php -- --version=2.9.8 --install-dir=/usr/local/bin --filename=composer

RUN rm -f /usr/local/etc/php-fpm.d/zz-docker.conf \
    /usr/local/etc/php-fpm.d/docker.conf \
    /usr/local/etc/php-fpm.d/www.conf.default \
  && rm -f /etc/nginx/sites-enabled/default

COPY conf/msmtprc /etc/msmtprc
COPY conf/php.ini $PHP_INI_DIR/conf.d/zz-corsair-magento.ini
COPY conf/php-xdebug.ini $PHP_INI_DIR/conf.d/zz-xdebug.ini
COPY conf/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY conf/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY conf/nginx.conf /etc/nginx/nginx.conf
COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN chmod +x /usr/local/bin/start-container \
  && git config --global --add safe.directory /var/www/html

WORKDIR /var/www/html
EXPOSE 80

ENTRYPOINT ["start-container"]
