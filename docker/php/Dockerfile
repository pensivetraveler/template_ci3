# template_ci3 검증 서버용 PHP (composer.json config.platform.php 7.4.33 과 같은 7.4)
FROM php:7.4-apache

# php:7.4 의 기반 Debian bullseye 는 지원이 끝나 archive.debian.org 로 옮겨졌다
RUN printf 'deb http://archive.debian.org/debian bullseye main\ndeb http://archive.debian.org/debian-security bullseye-security main\n' > /etc/apt/sources.list \
    && apt-get -o Acquire::Check-Valid-Until=false update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# 저장소 .htaccess(RewriteRule·SetEnv CI_ENV)가 동작하도록
RUN sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

COPY php.ini /usr/local/etc/php/conf.d/template.ini
