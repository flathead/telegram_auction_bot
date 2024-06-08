<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';  // Название команды
    protected string $description = 'Команда для запуска бота';  // Описание команды

    public function handle()
    {
        file_put_contents('php://stderr', "Handling StartCommand\n"); // Запись в лог для отладки
        try {
            $telegram = $this->getTelegram();  // Получаем объект Telegram
            $update = $this->getUpdate();  // Получаем обновление
            // Отправка приветственного сообщения пользователю
            $telegram->sendMessage([
                'chat_id' => $update->getMessage()->getChat()->getId(),
                'text' => "<b>Добро пожаловать в бот аукциона!</b>\n\nИспользуйте команду /list чтобы увидеть список активных аукционов.",
                'parse_mode' => 'HTML',  // Используем HTML разметку 
            ]);
        } catch (\Exception $e) {
            file_put_contents('php://stderr', "Error in StartCommand: " . $e->getMessage() . "\n");  // Запись ошибки в лог
        }
    }
}
