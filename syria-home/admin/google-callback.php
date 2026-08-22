<?php
require_once __DIR__ . '/_guard.php';

if (!empty($_GET['error'])) {
    header('Location: index.php?page=settings&tab=api&google_error=' . urlencode($_GET['error']));
    exit;
}

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if ($code === '' || !hash_equals($_SESSION['csrf'] ?? '', $state)) {
    header('Location: index.php?page=settings&tab=api&google_error=' . urlencode('invalid_state'));
    exit;
}

$resp = GoogleOAuth::exchangeCode($code);

if (!empty($resp['access_token'])) {
    log_ai_activity('google_connect', 'integration', null, 'Google account connected (AdSense/Search Console/Analytics/Ads scopes).');
    header('Location: index.php?page=settings&tab=api&connected=1');
} else {
    header('Location: index.php?page=settings&tab=api&google_error=' . urlencode($resp['error_description'] ?? $resp['error'] ?? 'unknown_error'));
}
