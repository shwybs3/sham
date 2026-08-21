<?php
/* Google OAuth — redirect to Google consent screen */
require_once dirname(__DIR__) . '/config.php';

if (!GOOGLE_CLIENT_ID) {
    header('Location: ' . SOCIAL_URL . '/auth/login?err=google_not_configured');
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
