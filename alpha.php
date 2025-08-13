<?php
header('Content-Type: application/json');

// Параметры из строки подключения
$redisHost = 'red-d2e7dqbuibrs738pnikg';
$redisPort = 6379;
$redisPass = 'LBxHE0RK3vJvqOYYI6paEeVUwAaX503m';
$timeout = 2; // Таймаут в секундах

$redis = new Redis();
try {
    // 1. Подключение
    if (!$redis->connect($redisHost, $redisPort, $timeout)) {
        throw new Exception("Не удалось подключиться к серверу Redis");
    }

    // 2. Аутентификация
    if (!$redis->auth($redisPass)) {
        throw new Exception("Неверный пароль Redis");
    }

    // 3. Проверка подключения
    $pong = $redis->ping();
    if ($pong !== '+PONG') {
        throw new Exception("Неверный ответ от Redis: " . $pong);
    }

    // 4. Пример использования (блокировка)
    $lockKey = 'alpha_lock';
    $lockTtl = 2; // В секундах

    if (!$redis->set($lockKey, 1, ['nx', 'ex' => $lockTtl])) {
        die(json_encode(['status' => 'error', 'message' => 'Операция уже выполняется']));
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