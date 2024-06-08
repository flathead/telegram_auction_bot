<?php

// Загрузка автозагрузчика Composer
require 'vendor/autoload.php';

use Telegram\Bot\Api;
use Dotenv\Dotenv;

// Загрузка переменных окружения из файла .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Получение URL вебхука из аргументов командной строки
$webhookUrl = $argv[1] . '/index.php';

// Создание экземпляра API Telegram
$telegram = new Api($_ENV['TELEGRAM_BOT_TOKEN']);

// Установка вебхука
$response = $telegram->setWebhook(['url' => $webhookUrl]);

// Вывод результата установки вебхука
if ($response) {
    echo "Webhook успешно установлен: $webhookUrl\n";
} else {
    echo "Не удалось установить webhook.\n";
}
