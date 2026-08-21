<?php
/**
 * tools/chat/ipn.php — NOWPayments IPN webhook for ياسمين subscriptions.
 *
 * Called by NOWPayments when a payment status changes. Validates the
 * HMAC-SHA512 signature, updates the order, and activates the subscription
 * if the payment is confirmed.
 */
$mainCfg = dirname(dirname(__DIR__)) . '/config.php';
if (!file_exists($mainCfg)) { http_response_code(503); exit; }
require_once $mainCfg;
require_once __DIR__ . '/_auth.php';

// Only POST is valid
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$ok = nowpay_handle_ipn($pdo);
http_response_code($ok ? 200 : 400);
echo $ok ? 'OK' : 'ERROR';
