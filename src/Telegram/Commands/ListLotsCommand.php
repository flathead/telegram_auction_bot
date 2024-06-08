<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

class ListLotsCommand extends Command
{
    protected string $name = 'list';  // Название команды
    protected string $description = 'Список активных лотов';  // Описание команды

    public function handle()
    {
        file_put_contents('php://stderr', "Handling ListLotsCommand\n"); // Запись в лог для отладки
        try {
            $telegram = $this->getTelegram();  // Получаем объект Telegram
            $update = $this->getUpdate();  // Получаем обновление
            file_put_contents('php://stderr', "Получил обновление\n"); // Запись в лог для отладки

            $pdo = require __DIR__ . '/../../../database.php';  // Подключаем базу данных
            file_put_contents('php://stderr', "Подключился к базе\n"); // Запись в лог для отладки

            // Запрос к базе данных для получения всех активных лотов
            $stmt = $pdo->query("SELECT * FROM lots");
            $lots = $stmt->fetchAll();
            file_put_contents('php://stderr', "Список лотов получен\n"); // Запись в лог для отладки

            // Проверка наличия активных лотов
            if (empty($lots)) {
                file_put_contents('php://stderr', "В базе нет активных лотов\n"); // Запись в лог для отладки
                $telegram->sendMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'text' => 'Нет активных лотов.'
                ]);
                return;
            }

            // Создание клавиатуры с активными лотами
            $inlineKeyboard = [];
            foreach ($lots as $lot) {
                $inlineKeyboard[] = [['text' => $lot['title'], 'callback_data' => 'view_' . $lot['id']]];
            }

            $keyboard = new Keyboard([
                'inline_keyboard' => $inlineKeyboard
            ]);

            file_put_contents('php://stderr', "Отправил сообщение с активными лотами\n"); // Запись в лог для отладки перед отправкой сообщения

            // Получение ID сообщения и чата
            $messageId = $update->getCallbackQuery() ? $update->getCallbackQuery()->getMessage()->getMessageId() : null;
            $chatId = $update->getCallbackQuery() ? $update->getCallbackQuery()->getMessage()->getChat()->getId() : $update->getMessage()->getChat()->getId();

            // Обновление или отправка нового сообщения с активными лотами
            if ($messageId) {
                $telegram->editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => 'Активные лоты:',
                    'reply_markup' => $keyboard
                ]);
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Активные лоты:',
                    'reply_markup' => $keyboard
                ]);
            }
        } catch (\Exception $e) {
            file_put_contents('php://stderr', "Error in ListLotsCommand: " . $e->getMessage() . "\n");  // Запись ошибки в лог
        }
    }
}
