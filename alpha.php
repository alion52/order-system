<?php
header('Content-Type: application/json');

// Подключение к Redis
$redis = new Redis();
$redisUrl = parse_url(getenv('REDIS_URL'));
$redis->connect($redisUrl['host'], $redisUrl['port']);
$redis->auth($redisUrl['pass']);

// Блокировка на 1 секунду
$lockKey = 'alpha_lock';
if (!$redis->set($lockKey, 1, ['nx', 'ex' => 1])) {
    die(json_encode(['status' => 'error', 'message' => 'Операция уже выполняется']));
}

try {
    // Подключение к PostgreSQL
    $db = new PDO(getenv('DATABASE_URL'));

    // Генерация случайного заказа
    $productId = rand(1, 10);
    $quantity = rand(1, 5);

    $stmt = $db->prepare("INSERT INTO orders (product_id, quantity) VALUES (?, ?)");
    $stmt->execute([$productId, $quantity]);

    // Имитация задержки
    sleep(1);

    echo json_encode([
        'status' => 'success',
        'product_id' => $productId,
        'quantity' => $quantity
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    // Снятие блокировки (на всякий случай)
    $redis->del($lockKey);
}