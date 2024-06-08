<?php

use Dotenv\Dotenv;

// Загрузка переменных окружения из файла .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Очистка переменных окружения от пробелов и символов новой строки
$DB_HOST = trim($_ENV['DB_HOST']);
$DB_PORT = trim($_ENV['DB_PORT']);
$DB_DATABASE = trim($_ENV['DB_DATABASE']);
$DB_USERNAME = trim($_ENV['DB_USERNAME']);
$DB_PASSWORD = trim($_ENV['DB_PASSWORD']);

// Формирование DSN для подключения к базе данных
$dsn = "mysql:host={$DB_HOST};dbname={$DB_DATABASE};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Установление режима обработки ошибок
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Установление режима выборки по умолчанию
    PDO::ATTR_EMULATE_PREPARES   => false,                 // Отключение эмуляции подготавливаемых запросов
];

try {
    // Подключение к базе данных
    $pdo = new PDO($dsn, $DB_USERNAME, $DB_PASSWORD, $options);
} catch (\PDOException $e) {
    // Обработка ошибки подключения к базе данных
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Возвращение объекта PDO для использования в других частях приложения
return $pdo;
