<?php

$pdo = require __DIR__ . '/../../database.php';

// Создание таблицы лотов
$pdo->exec("CREATE TABLE IF NOT EXISTS lots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    starting_bid DECIMAL(10, 2) NOT NULL,
    current_bid DECIMAL(10, 2) DEFAULT NULL,
    total_bids INT DEFAULT 0,
    end_time TIMESTAMP NOT NULL,
    bid_step INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notified TINYINT(1) DEFAULT 0
)");

// Создание таблицы ставок
$pdo->exec("CREATE TABLE IF NOT EXISTS bids (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lot_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    bid_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE CASCADE
)");

// Создание таблицы участников аукциона
$pdo->exec("CREATE TABLE IF NOT EXISTS auction_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lot_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    telegram_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE CASCADE
)");
