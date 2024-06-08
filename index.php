<?php

// Загрузка автозагрузчика Composer и файла базы данных
require 'vendor/autoload.php';
require 'database.php';

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use App\Telegram\Commands\StartCommand;
use App\Telegram\Commands\ListLotsCommand;
use App\Telegram\Commands\ViewLotCommand;
use App\Telegram\Commands\BidCommand;

// Загрузка переменных окружения из файла .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    // Создание экземпляра API Telegram
    $telegram = new Api($_ENV['TELEGRAM_BOT_TOKEN']);

    // Регистрация команд
    $telegram->addCommands([
        StartCommand::class,
        ListLotsCommand::class,
        ViewLotCommand::class,
        BidCommand::class,
    ]);

    // Обработка команд
    $telegram->commandsHandler(true);

    // Получение обновлений
    $update = $telegram->getWebhookUpdate();
    file_put_contents('php://stderr', print_r($update, true)); // Записываем обновления в stderr для отладки

    // Обработка callback-запросов
    if ($update->isType('callback_query')) {
        $data = $update->getCallbackQuery()->getData();
        
        // Обработка команды просмотра лота
        if (strpos($data, 'view_') === 0) {
            (new ViewLotCommand())->make($telegram, $update, ['data' => $data])->handle();
        }
        // Обработка команды ставки
        elseif (strpos($data, 'bid_') === 0) {
            (new BidCommand())->make($telegram, $update, ['data' => $data])->handle();
        }
        // Обработка команды списка лотов
        elseif ($data === 'list') {
            (new ListLotsCommand())->make($telegram, $update, ['message_id' => $update->getCallbackQuery()->getMessage()->getMessageId()])->handle();
        }

        // Ответ на нажатие кнопки
        $telegram->answerCallbackQuery([
            'callback_query_id' => $update->getCallbackQuery()->getId()
        ]);
    }
} catch (TelegramSDKException $e) {
    // Обработка исключений, связанных с SDK Telegram
    file_put_contents('php://stderr', "Error: " . $e->getMessage() . "\n");
}
