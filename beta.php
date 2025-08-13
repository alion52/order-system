<?php
declare(strict_types=1);
header('Content-Type: application/json');

// Конфигурация
const MAX_REQUESTS = 1000;
const REQUEST_TIMEOUT = 5; // секунд
const CONCURRENCY_LIMIT = 50; // одновременных запросов

// Обработка ошибок
function jsonError(string $message, int $code = 400): never {
    http_response_code($code);
    exit(json_encode(['status' => 'error', 'message' => $message]));
}

// Валидация параметра
$n = filter_input(INPUT_GET, 'n', FILTER_VALIDATE_INT, [
    'options' => [
        'default' => 100,
        'min_range' => 1,
        'max_range' => MAX_REQUESTS
    ]
]);

// Подготовка URL
$baseUrl = rtrim(getenv('BASE_URL') ?: 'https://your-service.onrender.com', '/');
$targetUrl = $baseUrl . '/alpha.php';

// Инициализация мультикурла
$mh = curl_multi_init();
$handles = [];
$active = 0;
$results = [
    'success' => 0,
    'errors' => 0,
    'status_codes' => []
];

// Создание запросов с ограничением concurrency
for ($i = 0; $i < $n || $active > 0;) {
    // Добавляем новые запросы пока не достигнем лимита
    while ($i < $n && $active < CONCURRENCY_LIMIT) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $targetUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => REQUEST_TIMEOUT,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-Request-ID: ' . uniqid()
            ]
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
        $active++;
        $i++;
    }

    // Выполнение запросов
    $status = curl_multi_exec($mh, $active);
    if ($status !== CURLM_OK) {
        error_log('curl_multi_exec error: ' . curl_multi_strerror($status));
        break;
    }

    // Обработка завершенных запросов
    while ($done = curl_multi_info_read($mh)) {
        $info = curl_getinfo($done['handle']);
        $httpCode = $info['http_code'] ?? 0;

        if ($done['result'] === CURLE_OK && $httpCode === 200) {
            $results['success']++;
        } else {
            $results['errors']++;
            error_log(sprintf(
                'Request failed: HTTP %d, error: %s',
                $httpCode,
                curl_error($done['handle'])
            ));
        }

        $results['status_codes'][$httpCode] = ($results['status_codes'][$httpCode] ?? 0) + 1;
        curl_multi_remove_handle($mh, $done['handle']);
        curl_close($done['handle']);
    }

    // Небольшая пауза для CPU
    if ($active) {
        curl_multi_select($mh, 0.1);
    }
}

// Очистка
foreach ($handles as $ch) {
    if (is_resource($ch)) {
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
}
curl_multi_close($mh);

// Результат
echo json_encode([
    'status' => 'completed',
    'total_requests' => $n,
    'results' => $results,
    'metrics' => [
        'duration' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
        'rps' => $n / max(1, microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'])
    ]
], JSON_PRETTY_PRINT);