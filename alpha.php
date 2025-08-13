<?php
header('Content-Type: application/json');

// Redis блокировка
$redis = new Redis();
try {
    $redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));
    if (!$redis->setnx('alpha_lock', 1)) {
        die(json_encode(['status' => 'error', 'message' => 'Script already running']));
    }
    $redis->expire('alpha_lock', 2); // Блокировка на 2 сек

    // Подключение к PostgreSQL
    $db = new PDO(getenv('DATABASE_URL'));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Генерация заказа
    $productId = rand(1, 10);
    $quantity = rand(1, 3);

    $stmt = $db->prepare("INSERT INTO orders (product_id, quantity) VALUES (?, ?)");
    $stmt->execute([$productId, $quantity]);

    sleep(1); // Искусственная задержка

    echo json_encode(['status' => 'success']);
} catch(Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    $redis->del('alpha_lock');
}
?>