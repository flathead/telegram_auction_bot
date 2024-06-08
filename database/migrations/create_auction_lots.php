<?php

$pdo = require __DIR__ . '/../../database.php';

echo 'Получаю данные продуктов...', PHP_EOL;

// Получение данных продуктов из категории Miscellaneous с ID 5
$categoryId = 5;
$apiUrl = "https://api.escuelajs.co/api/v1/products/?categoryId={$categoryId}";
$productResponse = file_get_contents($apiUrl);

if ($productResponse === false) {
    // Проверка успешного получения данных из API
    throw new Exception("Не удалось получить данные из API.");
}

$products = json_decode($productResponse, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Проверка на наличие ошибок при декодировании JSON
    throw new Exception("Ошибка при декодировании JSON: " . json_last_error_msg());
}

if (empty($products)) {
    // Проверка на наличие продуктов в категории
    throw new Exception("Нет продуктов с категорией под ID 5.");
}

echo 'Заполняю таблицы базы данных...', PHP_EOL;

// Создаем массив с уникальными продуктами
$uniqueProducts = [];
$addedProductIds = [];

foreach ($products as $product) {
    $productId = $product['id'];
    if (!in_array($productId, $addedProductIds)) {
        // Добавляем продукт, если его ID еще не был добавлен
        $addedProductIds[] = $productId;
        $uniqueProducts[] = $product;
    }
}

// Добавляем лоты в базу данных
for ($i = 0; $i < count($uniqueProducts); $i++) {
    $product = $uniqueProducts[$i];
    
    $title = $product['title'];
    $description = $product['description'];
    $starting_bid = $product['price'];
    $bid_step = rand(30, 120); // Генерируем случайный шаг ставки
    $end_time_rand_day = (string) "+". rand(1, 5) ."day"; // Генерируем случайное время окончания аукциона
    $end_time = date('Y-m-d H:i:s', strtotime($end_time_rand_day)); // Преобразуем в формат даты
    $image_url = $product['images'][0] ?? 'https://via.placeholder.com/300x300'; // Заглушка если нет изображения

    echo "Добавляю продукт: {$title}", PHP_EOL;

    try {
        // Добавляем продукт в базу данных
        $stmt = $pdo->prepare("INSERT INTO lots (title, description, image_url, starting_bid, current_bid, end_time, bid_step) VALUES (:title, :description, :image_url, :starting_bid, :current_bid, :end_time, :bid_step)");
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'image_url' => $image_url,
            'starting_bid' => $starting_bid,
            'current_bid' => $starting_bid,
            'end_time' => $end_time,
            'bid_step' => $bid_step
        ]);
        echo "Продукт добавлен: {$title}", PHP_EOL;
    } catch (\Exception $e) {
        // Обработка ошибок при добавлении продукта
        echo "Ошибка при добавлении продукта: " . $e->getMessage(), PHP_EOL;
    }
}

echo count($uniqueProducts) . " лот(ов) успешно созданы и добавлены в базу данных." . PHP_EOL;
