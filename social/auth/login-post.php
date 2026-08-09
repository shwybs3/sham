<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . SOCIAL_URL . '/auth/login'); exit; }

$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email) {
    header('Location: ' . SOCIAL_URL . '/auth/login?err=invalid_creds'); exit;
}

$st = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$st->execute([$email]);
$user = $st->fetch();

if (!$user || !$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
    header('Location: ' . SOCIAL_URL . '/auth/login?err=invalid_creds'); exit;
}

if ($user['status'] !== 'active') {
    header('Location: ' . SOCIAL_URL . '/auth/login?err=' . urlencode('حسابك محظور أو غير نشط')); exit;
}

sc_login($user);
header('Location: ' . SOCIAL_URL);
exit;
