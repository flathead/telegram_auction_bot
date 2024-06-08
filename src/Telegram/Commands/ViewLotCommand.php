<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

class ViewLotCommand extends Command
{
    protected string $name = 'view';  // Название команды
    protected string $description = 'Отображает информацию о лоте';  // Описание команды

    public function handle()
    {
        try {
            $pdo = require __DIR__ . '/../../../database.php';  // Подключение к базе данных
            $update = $this->getUpdate();  // Получение обновления
            $callbackQuery = $update->getCallbackQuery();  // Получение callback-запроса
            $lotId = str_replace('view_', '', $callbackQuery->getData());  // Извлечение ID лота из данных callback-запроса

            // Получение информации о лоте из базы данных
            $stmt = $pdo->prepare("SELECT * FROM lots WHERE id = :id");
            $stmt->execute(['id' => $lotId]);
            $lot = $stmt->fetch();

            // Формирование текста сообщения с информацией о лоте
            $text = "<b>Лот:</b> {$lot['title']}\n"
                  . "<b>Начальная ставка:</b> {$lot['starting_bid']}$\n"
                  . "<b>Текущая ставка:</b> {$lot['current_bid']}$\n"
                  . "<b>Всего ставок:</b> {$lot['total_bids']}$\n"
                  . "<b>Шаг ставки:</b> {$lot['bid_step']}$\n"
                  . "<b>Окончание аукциона:</b> {$lot['end_time']}\n\n"
                  . "<a href=\"{$lot['image_url']}\">&#8205;</a>";  // Добавление изображения без использования media

            // Создание клавиатуры с кнопками для взаимодействия
            $keyboard = new Keyboard([
                'inline_keyboard' => [
                    [
                        ['text' => 'Сделать ставку', 'callback_data' => 'bid_' . $lot['id']],
                        ['text' => 'Обновить', 'callback_data' => 'view_' . $lot['id']]
                    ],
                    [
                        ['text' => 'Назад', 'callback_data' => 'list']
                    ],
                ]
            ]);

            // Обновление сообщения с лотом
            $this->getTelegram()->editMessageText([
                'chat_id' => $update->getCallbackQuery()->getMessage()->getChat()->getId(),
                'message_id' => $update->getCallbackQuery()->getMessage()->getMessageId(),
                'text' => "<b>Обновлено: " . date('Y-m-d H:i:s', time()) . "</b>\n\n" . $text,
                'reply_markup' => $keyboard,
                'parse_mode' => 'HTML'  // Использование HTML разметки
            ]);

            // Ответ на нажатие кнопки
            $this->getTelegram()->answerCallbackQuery([
                'callback_query_id' => $update->getCallbackQuery()->getId()
            ]);
        } catch (\Exception $e) {
            file_put_contents('php://stderr', "ViewLotCommand Error: " . $e->getMessage() . "\n");  // Запись ошибки в лог
        }
    }
}
