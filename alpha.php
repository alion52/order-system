<?php
header('Content-Type: application/json');

// Redis блокировка
$redis = new Redis();
try {
    // Подключение к Redis с аутентификацией
    $redis->connect('red-d2e7dqbuibrs738pnikg', 6379); // Хост из Yandex Cloud
    $redis->auth('LBxHE0RK3vJvqOYYI6paEeVUwAaX503m'); // Пароль из Internal URL

    // Проверка подключения
    if ($redis->ping() != '+PONG') {
        throw new Exception('Redis connection failed');
    }

    // Установка блокировки с атомарным TTL
    $lockAcquired = $redis->set('alpha_lock', 1, ['nx', 'ex' => 2]);
    if (!$lockAcquired) {
        die(json_encode(['status' => 'error', 'message' => 'Script already running']));
    }

    // Подключение к PostgreSQL
    $db = new PDO(
        getenv('DATABASE_URL'),
        null,
        null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );

    // Генерация заказа
    $productId = rand(1, 10);
    $quantity = rand(1, 3);

    $stmt = $db->prepare("INSERT INTO orders (product_id, quantity) VALUES (?, ?) RETURNING id");
    $stmt->execute([$productId, $quantity]);
    $orderId = $stmt->fetchColumn();

    sleep(1); // Искусственная задержка

    echo json_encode([
        'status' => 'success',
        'order_id' => $orderId,
        'product_id' => $productId,
        'quantity' => $quantity
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Безопасное освобождение блокировки с повторной аутентификацией
    if (isset($redis)) {
        try {
            $redis->auth('LBnHE0RK3uJwQYYf6paEeVUwAaX5O3m');
            if ($redis->get('alpha_lock') === '1') {
                $redis->del('alpha_lock');
            }
        } catch (Exception $e) {
            error_log("Failed to release lock: " . $e->getMessage());
        }
    }
}
?>