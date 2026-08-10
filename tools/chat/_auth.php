<?php
/**
 * Yasmin chat — account helpers (Google OAuth + email/password).
 *
 * Loaded by tools/chat/index.php and tools/chat/auth.php. Both of those
 * require the main config.php first, so $pdo, the session, get_cfg()/set_cfg()
 * and csrf_token()/csrf_check() are already available here.
 *
 * Signing in is always optional: a signed-out visitor keeps the cookie-based
 * yasmin_sid flow that existed before accounts, so nothing regresses for them.
 */

// Failed sign-ins per IP before the form stops accepting attempts, and the
// window those failures are counted over.
const YS_LOGIN_MAX_FAILS   = 8;
const YS_LOGIN_WINDOW_MIN  = 15;
// New accounts per IP per hour.
const YS_SIGNUP_MAX_PER_IP = 5;
const YS_PASS_MIN          = 8;

/* ── Conversation scoping ────────────────────────────────────────── */

/**
 * Which conversations the caller may see.
 *
 * Signed in  → everything owned by the account, on any device.
 * Signed out → only rows still tied to this browser's cookie and not yet
 *              claimed by an account, so signing out really does hide the
 *              conversations that were adopted at sign-in.
 *
 * Returns [sqlFragment, params] to splice into a WHERE clause.
 *
 * Lives here rather than in chat/index.php because Szad loads this file
 * without loading the ياسمين page — every Szad request used to fatal on
 * "Call to undefined function ys_scope()", which surfaced to users as a
 * chat bubble that stayed permanently empty.
 */
function ys_scope(?array $user, string $sid): array {
    if ($user) return ['user_id = ?', [(int)$user['id']]];
    return ['session_id = ? AND user_id IS NULL', [$sid]];
}

/** User messages sent in the last hour, by account when known, else by cookie. */
function ys_recent_msg_count(PDO $pdo, ?array $user, string $sid): int {
    try {
        if ($user) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM yasmin_messages m
                                 JOIN yasmin_conversations c ON c.id = m.conversation_id
                                 WHERE c.user_id = ? AND m.role='user'
                                   AND m.created_at > (NOW() - INTERVAL 1 HOUR)");
            $st->execute([(int)$user['id']]);
        } else {
            if ($sid === '') return 0;
            $st = $pdo->prepare("SELECT COUNT(*) FROM yasmin_messages m
                                 JOIN yasmin_conversations c ON c.id = m.conversation_id
                                 WHERE c.session_id = ? AND m.role='user'
                                   AND m.created_at > (NOW() - INTERVAL 1 HOUR)");
            $st->execute([$sid]);
        }
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

/* ── Session ─────────────────────────────────────────────────────── */

/** The signed-in user row, or null. Result is cached per request. */
function ys_current_user(PDO $pdo): ?array
{
    static $cached = false;
    if ($cached !== false) return $cached;

    $uid = (int)($_SESSION['ys_uid'] ?? 0);
    if (!$uid) { $cached = null; return null; }

    try {
        $st = $pdo->prepare("SELECT * FROM yasmin_users WHERE id=? LIMIT 1");
        $st->execute([$uid]);
        $u = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $cached = null;
        return null;
    }

    // A ban takes effect on the banned user's next request rather than only at
    // sign-in, so revoking access does not wait for their session to expire.
    if ($u && $u['status'] !== 'active') {
        ys_logout();
        $cached = null;
        return null;
    }

    $cached = $u;
    return $u;
}

/** Establish the signed-in session for $user. */
function ys_login(PDO $pdo, array $user): void
{
    // New session ID on privilege change, so a fixated pre-login ID is useless.
    session_regenerate_id(true);
    $_SESSION['ys_uid'] = (int)$user['id'];

    try {
        $pdo->prepare("UPDATE yasmin_users SET last_login_at=NOW(), last_ip=? WHERE id=?")
            ->execute([$_SERVER['REMOTE_ADDR'] ?? null, (int)$user['id']]);
    } catch (Throwable $e) {}

    // Adopt any conversations this browser started while signed out.
    $sid = $_COOKIE['yasmin_sid'] ?? '';
    if ($sid !== '') {
        try {
            $pdo->prepare("UPDATE yasmin_conversations SET user_id=? WHERE session_id=? AND user_id IS NULL")
                ->execute([(int)$user['id'], $sid]);
        } catch (Throwable $e) {}
    }
}

function ys_logout(): void
{
    unset($_SESSION['ys_uid']);
}

/* ── Lookups / creation ──────────────────────────────────────────── */

function ys_find_by_email(PDO $pdo, string $email): ?array
{
    try {
        $st = $pdo->prepare("SELECT * FROM yasmin_users WHERE email=? LIMIT 1");
        $st->execute([mb_strtolower(trim($email))]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) { return null; }
}

function ys_find_by_google(PDO $pdo, string $googleId): ?array
{
    try {
        $st = $pdo->prepare("SELECT * FROM yasmin_users WHERE google_id=? LIMIT 1");
        $st->execute([$googleId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) { return null; }
}

/**
 * Create a password account. Returns [user|null, errorMessage].
 */
function ys_register(PDO $pdo, string $email, string $name, string $password): array
{
    $email = mb_strtolower(trim($email));
    $name  = trim($name);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [null, 'البريد الإلكتروني غير صالح.'];
    if (mb_strlen($password) < YS_PASS_MIN)         return [null, 'كلمة المرور يجب أن تكون ' . YS_PASS_MIN . ' أحرف على الأقل.'];
    if ($name === '')                               return [null, 'الرجاء إدخال اسمك.'];
    if (mb_strlen($name) > 60)                      $name = mb_substr($name, 0, 60);

    if (ys_signup_throttled($pdo)) {
        return [null, 'تم إنشاء حسابات كثيرة من هذا الاتصال. حاول بعد ساعة.'];
    }

    $existing = ys_find_by_email($pdo, $email);
    if ($existing) {
        // A Google-first account can claim a password later; a password account
        // that already exists is a duplicate.
        if ($existing['pass_hash']) return [null, 'هذا البريد مسجّل مسبقاً. سجّل الدخول بدلاً من ذلك.'];
        try {
            $pdo->prepare("UPDATE yasmin_users SET pass_hash=? WHERE id=?")
                ->execute([password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), (int)$existing['id']]);
        } catch (Throwable $e) { return [null, 'تعذّر إنشاء الحساب، حاول مجدداً.']; }
        return [ys_find_by_email($pdo, $email), ''];
    }

    try {
        $pdo->prepare("INSERT INTO yasmin_users (email,name,pass_hash,provider,last_ip) VALUES (?,?,?,'password',?)")
            ->execute([
                $email,
                $name,
                password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
    } catch (Throwable $e) {
        return [null, 'تعذّر إنشاء الحساب، حاول مجدداً.'];
    }

    log_security_event($pdo, 'yasmin_signup', 'info', 'New Yasmin account: ' . $email);
    return [ys_find_by_email($pdo, $email), ''];
}

/**
 * Find or create the account behind a verified Google profile.
 * $profile comes from Google's userinfo endpoint.
 */
function ys_upsert_google(PDO $pdo, array $profile): ?array
{
    $googleId = (string)($profile['sub'] ?? '');
    $email    = mb_strtolower(trim((string)($profile['email'] ?? '')));
    $name     = trim((string)($profile['name'] ?? '')) ?: (explode('@', $email)[0] ?? 'مستخدم');
    $avatar   = (string)($profile['picture'] ?? '');

    if ($googleId === '' || $email === '') return null;

    $user = ys_find_by_google($pdo, $googleId) ?: ys_find_by_email($pdo, $email);

    try {
        if ($user) {
            $pdo->prepare("UPDATE yasmin_users SET google_id=?, name=?, avatar=? WHERE id=?")
                ->execute([$googleId, mb_substr($name, 0, 60), mb_substr($avatar, 0, 255), (int)$user['id']]);
        } else {
            $pdo->prepare("INSERT INTO yasmin_users (email,name,avatar,google_id,provider,last_ip) VALUES (?,?,?,?,'google',?)")
                ->execute([
                    $email,
                    mb_substr($name, 0, 60),
                    mb_substr($avatar, 0, 255),
                    $googleId,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
            log_security_event($pdo, 'yasmin_signup', 'info', 'New Yasmin Google account: ' . $email);
        }
    } catch (Throwable $e) {
        return null;
    }

    return ys_find_by_google($pdo, $googleId);
}

/* ── Throttling ──────────────────────────────────────────────────── */

/** Count recent security_log rows of $type from this IP. */
function ys_recent_events(PDO $pdo, string $type, int $minutes): int
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip === '') return 0;
    // $minutes is cast and inlined rather than bound: MySQL will not accept a
    // placeholder for an INTERVAL quantity under every prepare mode. It only
    // ever comes from the constants above, never from request data.
    $minutes = max(1, (int)$minutes);
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM security_log
                             WHERE ip=? AND event_type=? AND created_at > (NOW() - INTERVAL $minutes MINUTE)");
        $st->execute([$ip, $type]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

function ys_login_locked(PDO $pdo): bool
{
    return ys_recent_events($pdo, 'yasmin_login_fail', YS_LOGIN_WINDOW_MIN) >= YS_LOGIN_MAX_FAILS;
}

function ys_record_login_fail(PDO $pdo, string $email): void
{
    // The address that was tried is recorded; the password never is.
    log_security_event($pdo, 'yasmin_login_fail', 'warning', 'Failed Yasmin sign-in for ' . mb_substr($email, 0, 120));
}

function ys_signup_throttled(PDO $pdo): bool
{
    return ys_recent_events($pdo, 'yasmin_signup', 60) >= YS_SIGNUP_MAX_PER_IP;
}

/* ── Google OAuth ────────────────────────────────────────────────── */

function ys_google_enabled(PDO $pdo): bool
{
    return get_cfg($pdo, 'yasmin_google_client_id', '') !== ''
        && get_cfg($pdo, 'yasmin_google_client_secret', '') !== '';
}

/**
 * The redirect URI handed to Google. Must match a URI registered on the OAuth
 * client exactly, so it is settable in admin; the default is derived from the
 * current request, which is right for both yassota.com/tools/chat/ and a
 * chat.yassota.com document root.
 */
function ys_google_redirect_uri(PDO $pdo): string
{
    $override = trim(get_cfg($pdo, 'yasmin_google_redirect', ''));
    if ($override !== '') return $override;

    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'yassota.com';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/tools/chat/index.php')), '/');
    return $scheme . '://' . $host . $dir . '/auth.php?a=google_cb';
}

function ys_google_auth_url(PDO $pdo): string
{
    $state = bin2hex(random_bytes(16));
    $_SESSION['ys_oauth_state'] = $state;

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => get_cfg($pdo, 'yasmin_google_client_id', ''),
        'redirect_uri'  => ys_google_redirect_uri($pdo),
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'prompt'        => 'select_account',
    ]);
}

/**
 * Exchange an authorization code for the Google profile.
 * Returns the userinfo array, or null on any failure.
 */
function ys_google_profile(PDO $pdo, string $code): ?array
{
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $code,
            'client_id'     => get_cfg($pdo, 'yasmin_google_client_id', ''),
            'client_secret' => get_cfg($pdo, 'yasmin_google_client_secret', ''),
            'redirect_uri'  => ys_google_redirect_uri($pdo),
            'grant_type'    => 'authorization_code',
        ]),
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    if (!$raw) return null;

    $tok = json_decode($raw, true);
    $accessToken = $tok['access_token'] ?? '';
    if (!$accessToken) return null;

    $ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
    ]);
    $rawProfile = curl_exec($ch);
    curl_close($ch);
    if (!$rawProfile) return null;

    $profile = json_decode($rawProfile, true);
    return is_array($profile) ? $profile : null;
}

/* ── Presentation ────────────────────────────────────────────────── */

/** First letter for the fallback avatar when a user has no Google picture. */
function ys_initial(array $user): string
{
    $n = trim((string)($user['name'] ?? '')) ?: (string)($user['email'] ?? '?');
    return mb_strtoupper(mb_substr($n, 0, 1));
}

function ys_chat_url(): string
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/tools/chat/index.php')), '/');
    return $dir . '/';
}

/* ── Subscription helpers ────────────────────────────────────────── */

const YS_TIERS = [
    'plus'    => ['label' => 'ياسمين بلاس',    'price' => 5,  'hourly' => 300,  'color' => '#10b981'],
    'pro'     => ['label' => 'ياسمين برو',      'price' => 10, 'hourly' => 800,  'color' => '#3b82f6'],
    'premium' => ['label' => 'ياسمين بريميوم',  'price' => 20, 'hourly' => 9999, 'color' => '#8b5cf6'],
];

// Models served per tier.
const YS_TIER_MODELS = [
    'plus'    => ['google/gemini-flash-1.5', 'meta-llama/llama-3.1-70b-instruct:free', 'openrouter/auto'],
    'pro'     => ['openai/gpt-4o-mini', 'anthropic/claude-3-haiku', 'google/gemini-pro'],
    'premium' => ['anthropic/claude-3.5-sonnet', 'openai/gpt-4o', 'anthropic/claude-3-opus'],
];

/**
 * Active subscription row for $userId, or null.
 * Automatically marks expired rows so the check stays cheap.
 */
function ys_active_sub(PDO $pdo, int $userId): ?array
{
    try {
        // expire stale rows silently
        @$pdo->prepare("UPDATE yasmin_subscriptions SET status='expired'
                         WHERE user_id=? AND status='active' AND expires_at < NOW()")
             ->execute([$userId]);

        $st = $pdo->prepare("SELECT * FROM yasmin_subscriptions
                              WHERE user_id=? AND status='active' AND expires_at >= NOW()
                              ORDER BY expires_at DESC LIMIT 1");
        $st->execute([$userId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) { return null; }
}

/** Effective hourly message cap for a user, considering their subscription. */
function ys_hourly_cap(?array $user, PDO $pdo): int
{
    if (!$user) return YS_HOURLY_ANON;
    $sub = ys_active_sub($pdo, (int)$user['id']);
    if (!$sub) return YS_HOURLY_USER;
    return YS_TIERS[$sub['tier']]['hourly'] ?? YS_HOURLY_USER;
}

/** Model list to use for a given user (respects their tier). */
function ys_tier_models(?array $user, PDO $pdo): ?array
{
    if (!$user) return null; // caller falls back to free pool
    $sub = ys_active_sub($pdo, (int)$user['id']);
    if (!$sub) return null;
    return YS_TIER_MODELS[$sub['tier']] ?? null;
}

/** NOWPayments: create an invoice and return the JSON response or null on error. */
function nowpay_create_invoice(PDO $pdo, int $userId, string $tier, float $priceUsd, int $durationDays = 30): ?array
{
    $apiKey  = get_cfg($pdo, 'nowpay_api_key', '');
    if (!$apiKey) return null;

    // Record the pending order first so we have the order_id
    try {
        $pdo->prepare("INSERT INTO yasmin_orders (user_id,tier,price_usd,duration_days,payment_status)
                        VALUES (?,?,?,?,'pending')")
            ->execute([$userId, $tier, $priceUsd, $durationDays]);
        $orderId = (int)$pdo->lastInsertId();
    } catch (Throwable $e) { return null; }

    $payload = json_encode([
        'price_amount'    => $priceUsd,
        'price_currency'  => 'usd',
        'pay_currency'    => 'usdttrc20',
        'order_id'        => 'ys_' . $orderId,
        'order_description' => 'ياسمين ' . (YS_TIERS[$tier]['label'] ?? $tier) . ' — ' . $durationDays . ' يوم',
        'ipn_callback_url' => rtrim(defined('SITE_URL') ? SITE_URL : '', '/') . '/tools/chat/ipn.php',
        'success_url'      => rtrim(defined('SITE_URL') ? SITE_URL : '', '/') . '/tools/chat/?sub=ok',
        'cancel_url'       => rtrim(defined('SITE_URL') ? SITE_URL : '', '/') . '/tools/chat/pricing.php',
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.nowpayments.io/v1/payment');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json',
        ],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw ?: '{}', true);
    // /v1/payment returns 201 Created and uses payment_id (not id)
    if (!in_array($code, [200, 201], true) || empty($data['payment_id'])) {
        @$pdo->prepare("UPDATE yasmin_orders SET payment_status='api_error' WHERE id=?")
             ->execute([$orderId]);
        return null;
    }

    @$pdo->prepare("UPDATE yasmin_orders SET nowpay_invoice_id=?, payment_status='waiting' WHERE id=?")
         ->execute([$data['payment_id'], $orderId]);

    $data['_order_id'] = $orderId;
    return $data;
}

/** Verify and process a NOWPayments IPN request. Returns true if handled OK. */
function nowpay_handle_ipn(PDO $pdo): bool
{
    $ipnSecret = get_cfg($pdo, 'nowpay_ipn_secret', '');
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return false;

    if ($ipnSecret !== '') {
        $sig = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';
        $data = json_decode($raw, true);
        if (!is_array($data)) return false;
        // NOWPayments sorts the JSON keys before signing
        ksort($data);
        $expected = hash_hmac('sha512', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $ipnSecret);
        if (!hash_equals($expected, $sig)) {
            log_security_event($pdo, 'yasmin_ipn_bad_sig', 'warning', 'Bad NOWPayments IPN sig');
            return false;
        }
    } else {
        $data = json_decode($raw, true);
        if (!is_array($data)) return false;
    }

    $invoiceId     = (string)($data['invoice_id'] ?? '');
    $paymentId     = (string)($data['payment_id'] ?? '');
    $paymentStatus = (string)($data['payment_status'] ?? '');
    $orderId       = ltrim((string)($data['order_id'] ?? ''), 'ys_');

    // Store IPN status on the order
    try {
        $pdo->prepare("UPDATE yasmin_orders
                        SET payment_status=?, nowpay_pay_id=?, ipn_verified=1, updated_at=NOW()
                        WHERE id=? OR nowpay_invoice_id=?")
            ->execute([$paymentStatus, $paymentId, (int)$orderId, $invoiceId]);
    } catch (Throwable $e) {}

    // Only activate subscription on confirmed/finished statuses
    if (!in_array($paymentStatus, ['finished', 'confirmed', 'sending', 'partially_paid'], true)) {
        return true; // acknowledged, no activation needed yet
    }

    // Fetch the order
    try {
        $row = null;
        if ($orderId) {
            $st = $pdo->prepare("SELECT * FROM yasmin_orders WHERE id=? LIMIT 1");
            $st->execute([(int)$orderId]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!$row && $invoiceId) {
            $st = $pdo->prepare("SELECT * FROM yasmin_orders WHERE nowpay_invoice_id=? LIMIT 1");
            $st->execute([$invoiceId]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!$row) return false;

        $userId  = (int)$row['user_id'];
        $tier    = (string)$row['tier'];
        $days    = max(1, (int)$row['duration_days']);
        $price   = (float)$row['price_usd'];

        // Idempotent: don't create a duplicate subscription for the same order
        $dup = $pdo->prepare("SELECT id FROM yasmin_subscriptions WHERE order_id=? LIMIT 1");
        $dup->execute([(int)$row['id']]);
        if ($dup->fetch()) return true;

        // Extend from now or from current expiry if already subscribed
        $sub = ys_active_sub($pdo, $userId);
        $expiresAt = $sub
            ? date('Y-m-d H:i:s', strtotime($sub['expires_at']) + $days * 86400)
            : date('Y-m-d H:i:s', time() + $days * 86400);

        $pdo->prepare("INSERT INTO yasmin_subscriptions
                        (user_id,tier,price_usd,order_id,expires_at,status)
                        VALUES (?,?,?,?,?,'active')")
            ->execute([$userId, $tier, $price, (int)$row['id'], $expiresAt]);

        log_security_event($pdo, 'yasmin_sub_activated', 'info',
            "Activated $tier for user $userId until $expiresAt (order {$row['id']})");
    } catch (Throwable $e) { return false; }

    return true;
}
