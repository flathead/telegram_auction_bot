#!/bin/bash

# Запуск Apache в фоновом режиме
apachectl -D FOREGROUND &

# Загрузка переменных окружения из .env
export $(grep -v '^#' /var/www/html/.env | xargs)

# Очистка переменных окружения от пробелов и символов новой строки
DB_HOST=$(echo -n ${DB_HOST} | tr -d '\r' | tr -d '\n')
DB_PORT=$(echo -n ${DB_PORT} | tr -d '\r' | tr -d '\n')
DB_DATABASE=$(echo -n ${DB_DATABASE} | tr -d '\r' | tr -d '\n')
DB_USERNAME=$(echo -n ${DB_USERNAME} | tr -d '\r' | tr -d '\n')
DB_PASSWORD=$(echo -n ${DB_PASSWORD} | tr -d '\r' | tr -d '\n')
TELEGRAM_BOT_TOKEN=$(echo -n ${TELEGRAM_BOT_TOKEN} | tr -d '\r' | tr -d '\n')
NGROK_AUTHTOKEN=$(echo -n ${NGROK_AUTHTOKEN} | tr -d '\r' | tr -d '\n')

# Отладочные выводы для проверки переменных окружения
echo "DB_HOST=${DB_HOST}"
echo "DB_PORT=${DB_PORT}"
echo "DB_DATABASE=${DB_DATABASE}"
echo "DB_USERNAME=${DB_USERNAME}"
echo "DB_PASSWORD=${DB_PASSWORD}"
echo "TELEGRAM_BOT_TOKEN=${TELEGRAM_BOT_TOKEN}"
echo "NGROK_AUTHTOKEN=${NGROK_AUTHTOKEN}"

# Ожидание доступности базы данных
while ! nc -z ${DB_HOST} 3306; do
  echo "Ожидание базы данных..."
  sleep 3
done

# Проверка подключения к базе данных после доступности порта
until mysqladmin ping -h ${DB_HOST} -u${DB_USERNAME} -p${DB_PASSWORD} --silent; do
  echo "Ожидание подключения к базе данных..."
  sleep 3
done

# Выполнение миграций
echo "База данных доступна. Выполнение миграций..."
php migrate.php

# Запуск ngrok
ngrok http 80 > /dev/null &

# Получение ngrok URL и установка вебхука
sleep 5 # Ждем пока ngrok запустится
NGROK_URL=$(curl -s http://localhost:4040/api/tunnels | jq -r .tunnels[0].public_url)
echo "NGROK URL: $NGROK_URL"

# Установить вебхук с полученным URL
php /var/www/html/setWebhook.php $NGROK_URL

# Сохраняем процесс Apache
wait
