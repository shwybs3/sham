<?php
define('NARI_ADMIN', true);
require_once __DIR__ . '/auth.php';

$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
