FROM php:8.1-apache

# Установка зависимостей и расширений PHP
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip default-mysql-client curl jq netcat-openbsd cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка ngrok
RUN curl -s https://ngrok-agent.s3.amazonaws.com/ngrok.asc | tee /etc/apt/trusted.gpg.d/ngrok.asc > /dev/null \
    && echo "deb https://ngrok-agent.s3.amazonaws.com buster main" | tee /etc/apt/sources.list.d/ngrok.list \
    && apt-get update && apt-get install ngrok

# Копирование файлов проекта
COPY . /var/www/html

# Установка рабочего каталога
WORKDIR /var/www/html

# Настройка Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Установка прав на entrypoint.sh
RUN chmod +x /var/www/html/entrypoint.sh

# Установка прав
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Установка зависимостей проекта
RUN composer install

# Передача аргументов сборки как переменных окружения
ARG NGROK_AUTHTOKEN

ENV NGROK_AUTHTOKEN=${NGROK_AUTHTOKEN}

# Установка ngrok authtoken из переменной окружения
RUN ngrok authtoken ${NGROK_AUTHTOKEN}

# Настройка cron
RUN echo "* * * * * www-data /usr/local/bin/php /var/www/html/cron/check_auctions.php >> /var/log/cron.log 2>&1" > /etc/cron.d/auction_cron \
    && chmod 0644 /etc/cron.d/auction_cron \
    && crontab /etc/cron.d/auction_cron

# Запуск Entrypoint
CMD cron && /bin/bash /var/www/html/entrypoint.sh

EXPOSE 80
