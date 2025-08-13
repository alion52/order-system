<?php
declare(strict_types=1);
header('Content-Type: application/json');
header('Cache-Control: max-age=5, must-revalidate'); // Кэширование на 5 секунд

// Функция для обработки ошибок
function jsonResponse(array $data, int $statusCode = 200): never {
    http_response_code($statusCode);
    exit(json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));
}

try {
    // Подключение к БД с обработкой ошибок
    $dbUrl = getenv('DATABASE_URL');
    if (!$dbUrl) {
        throw new RuntimeException('Database configuration missing');
    }

    $db = new PDO($dbUrl);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Оптимизированный запрос с CTE (Common Table Expression)
    $query = "
        WITH last_orders AS (
            SELECT id, order_time 
            FROM orders 
            ORDER BY order_time DESC 
            LIMIT 100
        )
        SELECT 
            c.id AS category_id,
            c.name AS category_name,
            COUNT(o.id) AS orders_count,
            SUM(o.quantity) AS items_sold,
            MIN(lo.order_time) AS first_order,
            MAX(lo.order_time) AS last_order,
            EXTRACT(EPOCH FROM (MAX(lo.order_time) - MIN(lo.order_time)) AS interval_seconds
        FROM last_orders lo
        JOIN orders o ON lo.id = o.id
        JOIN products p ON o.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        GROUP BY c.id, c.name
    ";

    $stmt = $db->query($query);
    $results = $stmt->fetchAll();

    if (empty($results)) {
        jsonResponse(['status' => 'success', 'data' => [], 'message' => 'No orders found'], 200);
    }

    // Форматирование результата
    $response = [
        'status' => 'success',
        'data' => [
            'categories' => array_map(function($row) {
                return [
                    'id' => (int)$row['category_id'],
                    'name' => $row['category_name'],
                    'orders_count' => (int)$row['orders_count'],
                    'items_sold' => (int)$row['items_sold']
                ];
            }, $results),
            'time_interval' => [
                'first_order' => $results[0]['first_order'],
                'last_order' => $results[0]['last_order'],
                'seconds' => (float)$results[0]['interval_seconds'],
                'human_readable' => gmdate("H:i:s", (int)$results[0]['interval_seconds'])
            ],
            'stats' => [
                'total_orders' => array_sum(array_column($results, 'orders_count')),
                'total_items' => array_sum(array_column($results, 'items_sold'))
            ]
        ],
        'generated_at' => date('c')
    ];

    jsonResponse($response);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    jsonResponse(['status' => 'error', 'message' => 'Database operation failed'], 500);
} catch (Throwable $e) {
    error_log('System error: ' . $e->getMessage());
    jsonResponse(['status' => 'error', 'message' => 'Internal server error'], 500);
}