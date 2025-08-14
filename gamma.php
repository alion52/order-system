<?php
header('Content-Type: application/json');

try {
    $db = new PDO(getenv('DATABASE_URL'));

    // 1. Статистика по категориям (за последние 100 заказов)
    $stats = $db->query("
        SELECT 
            c.name AS category,
            COUNT(o.id) AS total_orders,
            SUM(o.quantity) AS total_items
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        WHERE o.id > (SELECT MAX(id) - 100 FROM orders)
        GROUP BY c.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Время между первым и последним заказом
    $timeDiff = $db->query("
        SELECT 
            MAX(created_at) - MIN(created_at) AS interval
        FROM (
            SELECT created_at 
            FROM orders 
            ORDER BY id DESC 
            LIMIT 100
        ) AS last_orders
    ")->fetchColumn();

    echo json_encode([
        'categories' => $stats,
        'time_interval' => $timeDiff
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}