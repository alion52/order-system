<?php
header('Content-Type: application/json');

$db = new PDO(getenv('DATABASE_URL'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Статистика по категориям
    $stmt = $db->query("
        SELECT c.name, COUNT(o.id) as orders_count, SUM(o.quantity) as items_sold
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        WHERE o.id IN (SELECT id FROM orders ORDER BY order_time DESC LIMIT 100)
        GROUP BY c.name
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Временной интервал
    $stmt = $db->query("
        SELECT 
            MAX(order_time) as last_order,
            MIN(order_time) as first_order,
            EXTRACT(EPOCH FROM (MAX(order_time) - MIN(order_time))) as interval_seconds
        FROM (SELECT order_time FROM orders ORDER BY order_time DESC LIMIT 100) as last_orders
    ");
    $timeData = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'categories' => $categories,
        'time_data' => $timeData
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>