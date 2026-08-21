<?php
require_once dirname(__DIR__) . '/config.php';
sc_logout();
header('Location: ' . SOCIAL_URL);
exit;
