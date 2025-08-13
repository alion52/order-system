<?php
declare(strict_types=1);
header('Content-Type: application/json');

function respondWithError(string $message, int $httpCode = 500): void {
    http_response_code($httpCode);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

try {
    // ===== 1. Подключение к Redis =====
    if (!class_exists('Redis')) {
        respondWithError('Redis extension not installed', 500);
    }

    $redis = new Redis();

    // Параметры подключения с fallback-значениями
    $redisHost = getenv('REDIS_HOST') ?: 'localhost';
    $redisPort = getenv('REDIS_PORT') ? (int)getenv('REDIS_PORT') : 6379;
    $redisPassword = getenv('REDIS_PASSWORD') ?: null;
    $redisTimeout = 2; // 2 секунды таймаут

    if (!$redis->connect($redisHost, $redisPort, $redisTimeout)) {
        respondWithError('Failed to connect to Redis', 503);
    }

    if ($redisPassword && !$redis->auth($redisPassword)) {
        respondWithError('Redis authentication failed', 403);
    }

    // ===== 2. Блокировка =====
    $lockKey = 'alpha_script_lock';
    $lockTtl = 2; // 2 секунды
    $acquiredLock = $redis->set($lockKey, time(), ['nx', 'ex' => $lockTtl]);

    if (!$acquiredLock) {
        respondWithError('Another instance is already processing', 429);
    }

    // ===== 3. Подключение к PostgreSQL =====
    $dbUrl = getenv('DATABASE_URL');
    if (!$dbUrl) {
        respondWithError('Database configuration missing', 500);
    }

    $dbOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $db = new PDO($dbUrl, null, null, $dbOptions);
    } catch (PDOException $e) {
        respondWithError('Database connection failed: ' . $e->getMessage(), 503);
    }

    // ===== 4. Создание заказа =====
    $productId = random_int(1, 3); // Более безопасная альтернатива rand()
    $quantity = random_int(1, 3);
    $delay = 1; // Искусственная задержка

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO orders (product_id, quantity) 
            VALUES (:product_id, :quantity)
        ");

        $stmt->execute([
            ':product_id' => $productId,
            ':quantity' => $quantity
        ]);

        $db->commit();

        // Искусственная задержка после успешной транзакции
        sleep($delay);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'product_id' => $productId,
                'quantity' => $quantity,
                'processing_time' => $delay
            ]
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        respondWithError('Database operation failed: ' . $e->getMessage(), 500);
    }
} catch (Exception $e) {
    respondWithError('Unexpected error: ' . $e->getMessage());
} finally {
    // Гарантированное освобождение блокировки
    if (isset($redis)) {
        try {
            $redis->del($lockKey);
        } catch (RedisException $e) {
            error_log('Failed to release Redis lock: ' . $e->getMessage());
        }
    }
}