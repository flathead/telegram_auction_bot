<?php

require 'vendor/autoload.php';

// Сначала выполнить миграции для создания таблиц
echo 'Создаю таблицы базы данных...', PHP_EOL;
require 'database/migrations/create_auction_tables.php';
echo 'Таблицы созданы.', PHP_EOL;

// Затем выполнить миграции для заполнения данных
echo 'Заполняю таблицы базы данных...', PHP_EOL;
require 'database/migrations/create_auction_lots.php';
echo 'Таблицы заполнены.', PHP_EOL;

echo 'Все миграции выполнены!', PHP_EOL;