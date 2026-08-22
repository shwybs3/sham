<?php
/**
 * gidx-functions.php — Google Indexing API tool helpers (yassota)
 */

function gidx_self_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? parse_url(SITE_URL, PHP_URL_HOST);
    return $scheme . '://' . $host . '/';
}

define('GIDX_FREE_LIMIT', 100);

function gidx_identifier(): string
{
    if (!empty($_COOKIE['gidx_id']) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE['gidx_id'])) {
        return $_COOKIE['gidx_id'];
    }
    $id = bin2hex(random_bytes(16));
    setcookie('gidx_id', $id, time() + 3600 * 24 * 365 * 2, '/', '', true, true);
    $_COOKIE['gidx_id'] = $id;
    return $id;
}

function gidx_quota(PDO $pdo, string $identifier): array
{
    $stmt = $pdo->prepare("SELECT * FROM gidx_quota WHERE identifier = ?");
    $stmt->execute([$identifier]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->prepare("INSERT INTO gidx_quota (identifier, used_count, `limit`) VALUES (?, 0, ?)")
            ->execute([$identifier, GIDX_FREE_LIMIT]);
        return ['identifier' => $identifier, 'used' => 0, 'limit' => GIDX_FREE_LIMIT, 'premium_active' => false, 'premium_until' => null];
    }

    $premiumActive = !empty($row['premium_until']) && strtotime($row['premium_until']) > time();
    if (!empty($row['premium_until']) && !$premiumActive) {
        $pdo->prepare("UPDATE gidx_quota SET premium_until = NULL WHERE identifier = ?")->execute([$identifier]);
        $row['premium_until'] = null;
    }

    return [
        'identifier'     => $identifier,
        'used'           => (int)$row['used_count'],
        'limit'          => (int)$row['limit'],
        'premium_active' => $premiumActive,
        'premium_until'  => $row['premium_until'],
    ];
}

function gidx_increment_usage(PDO $pdo, string $identifier): void
{
    $pdo->prepare("UPDATE gidx_quota SET used_count = used_count + 1 WHERE identifier = ?")->execute([$identifier]);
}

function gidx_grant_premium(PDO $pdo, string $identifier, int $days = 30): void
{
    gidx_quota($pdo, $identifier);
    $stmt = $pdo->prepare("SELECT premium_until FROM gidx_quota WHERE identifier = ?");
    $stmt->execute([$identifier]);
    $current = $stmt->fetchColumn();
    $base = ($current && strtotime($current) > time()) ? strtotime($current) : time();
    $newExpiry = date('Y-m-d H:i:s', $base + $days * 86400);
    $pdo->prepare("UPDATE gidx_quota SET premium_until = ? WHERE identifier = ?")->execute([$newExpiry, $identifier]);
}

function gidx_validate_key(string $raw): array
{
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'الملف ليس JSON صالحاً'];
    }
    if (($data['type'] ?? '') !== 'service_account') {
        return ['ok' => false, 'error' => 'هذا ليس ملف Service Account (type يجب أن يكون service_account)'];
    }
    if (empty($data['private_key']) || empty($data['client_email'])) {
        return ['ok' => false, 'error' => 'الملف ناقص: private_key أو client_email مفقود'];
    }
    return ['ok' => true, 'client_email' => $data['client_email']];
}

function gidx_store_key(PDO $pdo, string $identifier, string $raw, string $clientEmail): void
{
    $pdo->prepare("
        INSERT INTO gidx_keys (identifier, service_account_json, client_email, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE service_account_json = VALUES(service_account_json),
                                 client_email = VALUES(client_email), updated_at = NOW()
    ")->execute([$identifier, $raw, $clientEmail]);
}

function gidx_get_stored_key(PDO $pdo, string $identifier): ?array
{
    $stmt = $pdo->prepare("SELECT service_account_json, client_email FROM gidx_keys WHERE identifier = ?");
    $stmt->execute([$identifier]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function gidx_disconnect_key(PDO $pdo, string $identifier): void
{
    $pdo->prepare("DELETE FROM gidx_keys WHERE identifier = ?")->execute([$identifier]);
}

function gidx_get_access_token(string $serviceAccountJson): array
{
    $sa = json_decode($serviceAccountJson, true);
    if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) {
        return ['ok' => false, 'error' => 'مفتاح Service Account غير صالح'];
    }

    $now = time();
    $header  = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims  = [
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/indexing',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ];
    $b64 = fn($d) => rtrim(strtr(base64_encode(json_encode($d)), '+/', '-_'), '=');
    $signingInput = $b64($header) . '.' . $b64($claims);

    $signature = '';
    $ok = openssl_sign($signingInput, $signature, $sa['private_key'], 'SHA256');
    if (!$ok) {
        return ['ok' => false, 'error' => 'فشل توقيع JWT — تأكد أن private_key صالح'];
    }
    $jwt = $signingInput . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
        CURLOPT_TIMEOUT => 15,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($res === false) return ['ok' => false, 'error' => 'تعذّر الاتصال بـ Google: ' . $err];
    $data = json_decode($res, true);
    if (empty($data['access_token'])) {
        return ['ok' => false, 'error' => 'رفض Google المصادقة: ' . ($data['error_description'] ?? $data['error'] ?? 'غير معروف')];
    }
    return ['ok' => true, 'token' => $data['access_token']];
}

function gidx_submit_url(string $accessToken, string $url): array
{
    $ch = curl_init('https://indexing.googleapis.com/v3/urlNotifications:publish');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['url' => $url, 'type' => 'URL_UPDATED']),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false) return ['ok' => false, 'message' => 'تعذّر الاتصال بـ Google'];
    $data = json_decode($res, true);
    if ($code >= 200 && $code < 300) return ['ok' => true, 'message' => 'تم الإرسال بنجاح'];

    $msg = $data['error']['message'] ?? "خطأ HTTP $code";
    if ($code === 403) $msg = 'الحساب غير مالك للموقع في Search Console، أو Indexing API غير مفعّلة بمشروع Google Cloud';
    if ($code === 429) $msg = 'تجاوزت الحد اليومي المسموح من Google لهذا الحساب';
    return ['ok' => false, 'message' => $msg];
}

function nowpayments_create_payment(PDO $pdo, string $identifier, string $payCurrency): array
{
    $apiKey = get_cfg($pdo, 'nowpayments_api_key', '');
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'لم يتم إعداد مفتاح NOWPayments بعد من لوحة الإعدادات'];
    }

    $orderId = $identifier . '_' . bin2hex(random_bytes(6));

    $ch = curl_init('https://api.nowpayments.io/v1/payment');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'price_amount'      => 15,
            'price_currency'    => 'usd',
            'pay_currency'      => $payCurrency,
            'order_id'          => $orderId,
            'order_description' => 'اشتراك Premium — أداة فهرسة Google (30 يوماً)',
            'ipn_callback_url'  => rtrim(SITE_URL, '/') . '/gidx-payment-webhook.php',
        ]),
        CURLOPT_HTTPHEADER => ['x-api-key: ' . $apiKey, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT    => 20,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false) return ['ok' => false, 'error' => 'تعذّر الاتصال بـ NOWPayments'];
    $data = json_decode($res, true);
    if ($code < 200 || $code >= 300 || empty($data['pay_address'])) {
        return ['ok' => false, 'error' => 'فشل إنشاء الفاتورة: ' . ($data['message'] ?? "HTTP $code")];
    }

    $pdo->prepare("
        INSERT INTO gidx_payments (order_id, identifier, price_usd, pay_currency, pay_address, pay_amount, status)
        VALUES (?, ?, 15, ?, ?, ?, 'pending')
    ")->execute([$orderId, $identifier, $data['pay_currency'] ?? $payCurrency, $data['pay_address'], $data['pay_amount'] ?? 0]);

    return ['ok' => true, 'order_id' => $orderId, 'data' => $data];
}
