<?php
header('Content-Type: application/json');

try {
    $db = new PDO(getenv('DATABASE_URL'));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Оптимизированный запрос
    $stmt = $db->query("
        WITH last_orders AS (
            SELECT * FROM orders ORDER BY order_time DESC LIMIT 100
        )
        SELECT 
            c.name as category,
            COUNT(o.id) as orders_count,
            SUM(o.quantity) as items_sold,
            MIN(o.order_time) as first_order,
            MAX(o.order_time) as last_order
        FROM last_orders o
        JOIN products p ON o.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        GROUP BY c.id, c.name
    ");

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>