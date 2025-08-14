<?php
header('Content-Type: application/json');

$count = $_GET['count'] ?? 100; // Количество запусков

// Асинхронные запросы через cURL
$multiHandle = curl_multi_init();
$handles = [];

for ($i = 0; $i < $count; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://your-service.onrender.com/alpha.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100); // Таймаут 100 мс
    curl_multi_add_handle($multiHandle, $ch);
    $handles[] = $ch;
}

// Запуск всех запросов
do {
    curl_multi_exec($multiHandle, $running);
} while ($running > 0);

// Закрытие соединений
foreach ($handles as $ch) {
    curl_multi_remove_handle($multiHandle, $ch);
}
curl_multi_close($multiHandle);

echo json_encode(['status' => 'success', 'count' => $count]);