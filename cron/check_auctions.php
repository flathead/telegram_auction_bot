<?php

require 'vendor/autoload.php';
$pdo = require __DIR__ . '/database.php';
$telegram = new \Telegram\Bot\Api($_ENV['TELEGRAM_BOT_TOKEN']);

echo "Проверка завершения аукционов..." . PHP_EOL;

// Получаем все лоты, срок действия которых истек
$stmt = $pdo->query("SELECT * FROM lots WHERE end_time <= NOW() AND notified = 0");
$lots = $stmt->fetchAll();

foreach ($lots as $lot) {
    $lotId = $lot['id'];
    $currentBid = $lot['current_bid'];
    $title = $lot['title'];

    // Получаем всех участников, делавших ставки на этот лот
    $stmt = $pdo->prepare("SELECT DISTINCT telegram_id FROM auction_participants WHERE lot_id = :lot_id");
    $stmt->execute(['lot_id' => $lotId]);
    $participants = $stmt->fetchAll();

    // Отправляем уведомления всем участникам
    foreach ($participants as $participant) {
        try {
            $telegram->sendMessage([
                'chat_id' => $participant['telegram_id'],
                'text' => "Аукцион лота '{$title}' завершён. Финальная ставка: {$currentBid}."
            ]);
            echo "Уведомление отправлено участнику с Telegram ID: {$participant['telegram_id']}" . PHP_EOL;
        } catch (\Exception $e) {
            echo "Ошибка при отправке уведомления участнику с Telegram ID: {$participant['telegram_id']}: " . $e->getMessage() . PHP_EOL;
        }
    }

    // Обновляем статус уведомления для лота
    $stmt = $pdo->prepare("UPDATE lots SET notified = 1 WHERE id = :id");
    $stmt->execute(['id' => $lotId]);
}

echo "Уведомления о завершении аукционов отправлены." . PHP_EOL;
