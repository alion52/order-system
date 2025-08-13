<?php
header('Content-Type: application/json');

// Подключение к Redis
$redis = new Redis();
$redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));
if (getenv('REDIS_PASSWORD')) {
    $redis->auth(getenv('REDIS_PASSWORD'));
}

// Блокировка
$lockKey = 'alpha_script_lock';
if (!$redis->set($lockKey, 1, ['nx', 'ex' => 2])) {
    echo json_encode(['status' => 'error', 'message' => 'Script is already running']);
    exit;
}

// Подключение к PostgreSQL
$db = new PDO(getenv('DATABASE_URL'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Создание заказа
$productId = rand(1, 3);
$quantity = rand(1, 3);

try {
    $stmt = $db->prepare("INSERT INTO orders (product_id, quantity) VALUES (?, ?)");
    $stmt->execute([$productId, $quantity]);
    sleep(1); // Искусственная задержка
    echo json_encode(['status' => 'success', 'message' => 'Order created']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$redis->del($lockKey);
?><?php
