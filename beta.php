<?php
header('Content-Type: application/json');

$n = isset($_GET['n']) ? (int)$_GET['n'] : 1000;
if ($n <= 0) $n = 1000;

$url = getenv('BASE_URL') . '/alpha.php';
$mh = curl_multi_init();
$handles = [];

for ($i = 0; $i < $n; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_multi_add_handle($mh, $ch);
    $handles[] = $ch;
}

do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

foreach ($handles as $ch) {
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}

curl_multi_close($mh);

echo json_encode(['status' => 'success', 'message' => "$n requests sent"]);
?>