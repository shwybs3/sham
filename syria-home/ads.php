<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
$pub = trim(setting('adsense_publisher_id'));
if ($pub !== '') {
    $id = preg_replace('~[^0-9]~', '', $pub);
    echo "google.com, pub-{$id}, DIRECT, f08c47fec0942fa0\n";
} else {
    echo "# Add your AdSense Publisher ID in Settings > Advertisements to populate this file.\n";
}
