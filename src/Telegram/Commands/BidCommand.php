<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

class BidCommand extends Command
{
    protected string $name = 'bid';
    protected string $description = 'Сделать ставку на лот';

    public function handle()
    {
        file_put_contents('php://stderr', "Handling BidCommand\n"); // Запись в лог для отладки
        try {
            $telegram = $this->getTelegram();
            $update = $this->getUpdate();
            $pdo = require __DIR__ . '/../../../database.php';
            $data = $update->getCallbackQuery()->getData();
            $lotId = str_replace('bid_', '', $data);
            $userId = $update->getCallbackQuery()->getFrom()->getId();

            // Получение текущей информации о лоте
            $stmt = $pdo->prepare("SELECT title, current_bid, bid_step FROM lots WHERE id = :id");
            $stmt->execute(['id' => $lotId]);
            $lot = $stmt->fetch();

            if (!$lot) {
                throw new \Exception("Лот не найден");
            }

            // Вычисление новой ставки
            $currentBid = $lot['current_bid'];
            $bidStep = $lot['bid_step'];
            $bidAmount = $currentBid + $bidStep;

            // Запись ставки в базу данных
            $stmt = $pdo->prepare("INSERT INTO bids (lot_id, user_id, bid_amount) VALUES (:lot_id, :user_id, :bid_amount)");
            $stmt->execute([
                'lot_id' => $lotId,
                'user_id' => $userId,
                'bid_amount' => $bidAmount
            ]);

            // Обновление текущей ставки в лоте
            $stmt = $pdo->prepare("UPDATE lots SET current_bid = :current_bid, total_bids = total_bids + 1 WHERE id = :id");
            $stmt->execute([
                'current_bid' => $bidAmount,
                'id' => $lotId
            ]);

            // Запись информации об участнике аукциона
            $stmt = $pdo->prepare("INSERT INTO auction_participants (lot_id, user_id, telegram_id) VALUES (:lot_id, :user_id, :telegram_id) ON DUPLICATE KEY UPDATE telegram_id = VALUES(telegram_id)");
            $stmt->execute([
                'lot_id' => $lotId,
                'user_id' => $userId,
                'telegram_id' => $userId
            ]);

            // Получение обновленной информации о лоте
            $stmt = $pdo->prepare("SELECT * FROM lots WHERE id = :id");
            $stmt->execute(['id' => $lotId]);
            $lot = $stmt->fetch();

            // Уведомление всех участников, кроме текущего пользователя
            $stmt = $pdo->prepare("SELECT DISTINCT telegram_id FROM auction_participants WHERE lot_id = :lot_id AND telegram_id != :current_user_id");
            $stmt->execute(['lot_id' => $lotId, 'current_user_id' => $userId]);
            $participants = $stmt->fetchAll();

            $notificationText = "Другой пользователь сделал ставку на лот '<b>{$lot['title']}</b>'\n\n<b>Текущая ставка: {$bidAmount}$</b>";
            foreach ($participants as $participant) {
                $telegram->sendMessage([
                    'chat_id' => $participant['telegram_id'],
                    'text' => $notificationText,
                    'parse_mode' => 'HTML',
                ]);
            }

            $text = "<b>Ваша ставка сделана!</b>\n\n"
                . "Лот: {$lot['title']}\n"
                . "Начальная ставка: {$lot['starting_bid']}$\n"
                . "Текущая ставка: {$lot['current_bid']}$\n"
                . "Всего ставок: {$lot['total_bids']}$\n"
                . "Шаг ставки: {$lot['bid_step']}$\n"
                . "Завершение аукциона: {$lot['end_time']}"
                . "<a href=\"{$lot['image_url']}\">&#8205;</a>";

            $keyboard = new Keyboard([
                'inline_keyboard' => [
                    [
                        ['text' => 'Сделать ставку', 'callback_data' => 'bid_' . $lot['id']],
                        ['text' => 'Обновить', 'callback_data' => 'view_' . $lot['id']]
                    ],
                    [
                        ['text' => 'Назад', 'callback_data' => 'list'],
                    ]
                ]
            ]);

            // Обновление сообщения с лотом
            $telegram->editMessageText([
                'chat_id' => $update->getCallbackQuery()->getMessage()->getChat()->getId(),
                'message_id' => $update->getCallbackQuery()->getMessage()->getMessageId(),
                'text' => $text,
                'reply_markup' => $keyboard,
                'parse_mode' => 'HTML'
            ]);

            // Ответ на нажатие кнопки
            $telegram->answerCallbackQuery([
                'callback_query_id' => $update->getCallbackQuery()->getId()
            ]);
        } catch (\Exception $e) {
            file_put_contents('php://stderr', "Error in BidCommand: " . $e->getMessage() . "\n");
        }
    }
}
