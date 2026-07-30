<?php
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

$type = $_GET['type'] ?? 'exchange';

switch ($type) {
  case 'exchange':
    $base = strtoupper($_GET['base'] ?? 'USD');
    $data = get_exchange_rates($base);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    break;

  case 'gold':
    $data = get_gold_prices();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    break;

  default:
    http_response_code(400);
    echo json_encode(['error' => 'Unknown type. Use type=exchange or type=gold'], JSON_UNESCAPED_UNICODE);
}
