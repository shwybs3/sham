<?php
/* Google OAuth — callback handler */
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (!$code || $state !== ($_SESSION['oauth_state'] ?? '')) {
    header('Location: ' . SOCIAL_URL . '/auth/login?err=oauth_fail');
    exit;
}
unset($_SESSION['oauth_state']);

// Exchange code for token
$ctx = stream_context_create(['http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
    'content' => http_build_query([
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]),
    'ignore_errors' => true,
]]);
$tokenResp = @file_get_contents('https://oauth2.googleapis.com/token', false, $ctx);
$token     = json_decode($tokenResp ?: '', true);

if (empty($token['access_token'])) {
    header('Location: ' . SOCIAL_URL . '/auth/login?err=oauth_fail');
    exit;
}

// Get user info
$ctx2 = stream_context_create(['http' => ['header' => "Authorization: Bearer {$token['access_token']}\r\n"]]);
$info  = json_decode(@file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, $ctx2), true);

$googleId = $info['id']    ?? '';
$email    = $info['email'] ?? '';
$name     = $info['name']  ?? '';

if (!$googleId || !$email) {
    header('Location: ' . SOCIAL_URL . '/auth/login?err=oauth_fail');
    exit;
}

// Find or create user
$st = $pdo->prepare("SELECT * FROM users WHERE google_id=? OR email=? LIMIT 1");
$st->execute([$googleId, $email]);
$user = $st->fetch();

if (!$user) {
    // New user — need username
    $_SESSION['oauth_pending'] = ['google_id'=>$googleId,'email'=>$email,'name'=>$name];
    header('Location: ' . SOCIAL_URL . '/auth/setup');
    exit;
} else {
    // Update google_id if missing
    if (!$user['google_id']) {
        $pdo->prepare("UPDATE users SET google_id=? WHERE id=?")->execute([$googleId, $user['id']]);
    }
    sc_login($user);
    header('Location: ' . SOCIAL_URL);
    exit;
}
