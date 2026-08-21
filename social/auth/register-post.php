<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . SOCIAL_URL . '/auth/register'); exit; }

$username = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['username'] ?? ''));
$display  = trim($_POST['display_name'] ?? '');
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$bio      = trim($_POST['bio'] ?? '');

if (!$username || !$display || !$email || strlen($password) < 8) {
    header('Location: ' . SOCIAL_URL . '/auth/register?err=' . urlencode('بيانات غير مكتملة أو غير صالحة'));
    exit;
}

// Check duplicates
$dup = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
$dup->execute([$username, $email]);
if ($dup->fetchColumn()) {
    header('Location: ' . SOCIAL_URL . '/auth/register?err=username_taken');
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (username,display_name,bio,email,password_hash,status) VALUES (?,?,?,?,?,'active')")
    ->execute([$username, $display, $bio ?: null, $email, $hash]);
$id   = (int)$pdo->lastInsertId();
$user = $pdo->query("SELECT * FROM users WHERE id={$id}")->fetch();
sc_login($user);
header('Location: ' . SOCIAL_URL);
exit;
