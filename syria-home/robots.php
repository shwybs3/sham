<?php
/**
 * Dynamic robots.txt. The body itself lives in sh_robots_body()
 * (includes/functions.php) so the admin panel's "generate a physical
 * robots.txt" action writes exactly what this endpoint serves.
 */
require_once __DIR__ . '/config.php';
if (!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
echo sh_robots_body();
