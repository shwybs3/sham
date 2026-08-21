<?php
/**
 * includes/functions.php
 */

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function money($n) {
    return number_format((float)$n, 0);
}

function cssVersion(): string {
    $f = __DIR__ . '/../assets/css/style.css';
    return file_exists($f) ? substr(md5_file($f), 0, 8) : '1';
}

/* ── عملات مدعومة ── */
function getCurrencies(): array {
    return [
        'SYP' => ['symbol' => 'ل.س', 'name' => 'الليرة السورية'],
        'TRY' => ['symbol' => '₺',   'name' => 'الليرة التركية'],
        'USD' => ['symbol' => '$',   'name' => 'الدولار الأمريكي'],
    ];
}
function currencySymbol(string $code): string {
    return getCurrencies()[$code]['symbol'] ?? $code;
}

/* ── أنواع المنتجات ── */
function getProductTypes(): array {
    return [
        'بقالة'   => ['خبز','حليب','سكر','رز','زيت','طحين','بيض','جبن','زبدة','شاي','قهوة','معكرونة','معلبات','مياه','عصائر'],
        'مخبز'   => ['خبز','كعك','بسكويت','حلويات'],
        'لحوم'   => ['لحم غنم','لحم بقر','دجاج','سمك','كفتة','نقانق'],
        'خضار وفواكه' => ['بندورة','بطاطا','بصل','جزر','خيار','موز','تفاح','برتقال'],
        'مواد تنظيف' => ['صابون','شامبو','منظف','مبيض','جلي صحون'],
        'دواء'  => ['دواء','مسكن','فيتامين','ضماد'],
        'أخرى'  => [],
    ];
}

/* ── الإعدادات ── */
function getAllSettings(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    try {
        foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        error_log('getAllSettings: ' . $e->getMessage());
    }
    return $cache;
}
function setSetting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->execute(['k' => $key, 'v' => $value]);
}

/* ── العملاء والديون ── */
function getCustomerBalance(PDO $pdo, int $customerId): float {
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(CASE WHEN type='purchase' THEN amount ELSE -amount END),0) AS bal
         FROM transactions WHERE customer_id = ?"
    );
    $stmt->execute([$customerId]);
    return (float)$stmt->fetchColumn();
}

function getCustomersWithBalances(PDO $pdo, string $search = '', string $order = 'balance_desc'): array {
    $sql = "SELECT c.*,
              COALESCE(SUM(CASE WHEN t.type='purchase' THEN t.amount ELSE -t.amount END),0) AS balance,
              MAX(t.created_at) AS last_activity,
              COUNT(t.id) AS tx_count
            FROM customers c
            LEFT JOIN transactions t ON t.customer_id = c.id";
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE c.name LIKE ?";
        $params[] = '%' . $search . '%';
    }
    $sql .= " GROUP BY c.id";
    switch ($order) {
        case 'name_asc':  $sql .= " ORDER BY c.name ASC"; break;
        case 'recent':    $sql .= " ORDER BY last_activity DESC"; break;
        default:          $sql .= " ORDER BY balance DESC, c.name ASC"; break;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCustomer(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getCustomerTransactions(PDO $pdo, int $customerId): array {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE customer_id = ? ORDER BY created_at DESC, id DESC");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll();
}

function getTotalDebt(PDO $pdo): float {
    $stmt = $pdo->query(
        "SELECT COALESCE(SUM(CASE WHEN type='purchase' THEN amount ELSE -amount END),0) FROM transactions"
    );
    return (float)$stmt->fetchColumn();
}

function findOrCreateCustomer(PDO $pdo, string $name): array {
    $name = trim($name);
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $existing = $stmt->fetch();
    if ($existing) return $existing;
    $stmt = $pdo->prepare("INSERT INTO customers (name, created_at) VALUES (?, NOW())");
    $stmt->execute([$name]);
    $id = (int)$pdo->lastInsertId();
    return getCustomer($pdo, $id);
}

/* ── الإدارة ── */
function requireAdminLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}
function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrfCheck(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* ── مستخدمو الموقع (Google Login) ── */
function getSiteUser(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM site_users WHERE id = ? AND is_banned = 0");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getSiteUserByGoogleId(PDO $pdo, string $googleId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM site_users WHERE google_id = ?");
    $stmt->execute([$googleId]);
    return $stmt->fetch() ?: null;
}

function upsertSiteUser(PDO $pdo, string $googleId, string $email, string $name, string $avatar): array {
    $existing = getSiteUserByGoogleId($pdo, $googleId);
    if ($existing) {
        $pdo->prepare("UPDATE site_users SET email=?, display_name=?, avatar_url=?, last_seen=NOW() WHERE google_id=?")
            ->execute([$email, $name, $avatar, $googleId]);
        $existing['display_name'] = $name;
        $existing['avatar_url']   = $avatar;
        return $existing;
    }
    $pdo->prepare("INSERT INTO site_users (google_id,email,display_name,avatar_url,last_seen,created_at) VALUES (?,?,?,?,NOW(),NOW())")
        ->execute([$googleId, $email, $name, $avatar]);
    return getSiteUserByGoogleId($pdo, $googleId);
}

function getCurrentSiteUser(PDO $pdo): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['site_user_id'])) return null;
    return getSiteUser($pdo, (int)$_SESSION['site_user_id']);
}

function requireSiteLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['site_user_id'])) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
}

/* ── دردشة ── */
function getChatMessages(PDO $pdo, int $limit = 50, int $afterId = 0): array {
    $sql = "SELECT m.*, u.display_name, u.username, u.avatar_url
            FROM chat_messages m JOIN site_users u ON u.id = m.user_id
            WHERE m.id > ? AND u.is_banned = 0
            ORDER BY m.created_at ASC LIMIT ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$afterId, $limit]);
    return $stmt->fetchAll();
}

function addChatMessage(PDO $pdo, int $userId, string $message): int {
    $message = trim(mb_substr($message, 0, 500));
    if ($message === '') return 0;
    $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, message, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $message]);
    return (int)$pdo->lastInsertId();
}

/* ── أسعار الصرف ── */
function fetchExchangeRates(): array {
    $results = [];

    // المصدر الأول: frankfurter.app (مجاني، بدون مفتاح)
    $ch = curl_init('https://api.frankfurter.app/latest?from=USD&to=TRY,EUR,GBP');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'DukkhanApp/1.0']);
    $raw = curl_exec($ch);
    $ok  = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    if ($ok && $raw) {
        $data = json_decode($raw, true);
        if (!empty($data['rates'])) {
            foreach ($data['rates'] as $code => $rate) {
                $results[$code] = ['rate' => (float)$rate, 'source' => 'frankfurter'];
            }
            $results['USD'] = ['rate' => 1.0, 'source' => 'frankfurter'];
        }
    }

    // المصدر الثاني: open.er-api.com (مجاني)
    if (empty($results)) {
        $ch2 = curl_init('https://open.er-api.com/v6/latest/USD');
        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => 'DukkhanApp/1.0']);
        $raw2 = curl_exec($ch2);
        $ok2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch2);
        if ($ok2 && $raw2) {
            $data2 = json_decode($raw2, true);
            if (!empty($data2['rates'])) {
                foreach (['TRY', 'EUR', 'GBP', 'USD'] as $code) {
                    if (isset($data2['rates'][$code])) {
                        $results[$code] = ['rate' => (float)$data2['rates'][$code], 'source' => 'open.er-api'];
                    }
                }
            }
        }
    }

    return $results;
}

function saveExchangeRates(PDO $pdo, array $rates): void {
    $stmt = $pdo->prepare(
        "INSERT INTO exchange_rates (currency, rate_to_usd, source) VALUES (?, ?, ?)"
    );
    foreach ($rates as $code => $info) {
        $stmt->execute([$code, $info['rate'], $info['source']]);
    }
}

function getLatestRates(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT currency, rate_to_usd, source, recorded_at
         FROM exchange_rates
         WHERE (currency, recorded_at) IN (
           SELECT currency, MAX(recorded_at) FROM exchange_rates GROUP BY currency
         )
         ORDER BY currency"
    );
    return $stmt->fetchAll();
}

function getPreviousRate(PDO $pdo, string $currency): ?float {
    $stmt = $pdo->prepare(
        "SELECT rate_to_usd FROM exchange_rates
         WHERE currency = ? ORDER BY recorded_at DESC LIMIT 1 OFFSET 1"
    );
    $stmt->execute([$currency]);
    $row = $stmt->fetch();
    return $row ? (float)$row['rate_to_usd'] : null;
}

function checkAndAlertRateChange(PDO $pdo, array $newRates, string $token, string $chatId, float $threshold): void {
    if (!$token || !$chatId) return;
    foreach ($newRates as $code => $info) {
        $prev = getPreviousRate($pdo, $code);
        if ($prev && $prev > 0) {
            $change = abs(($info['rate'] - $prev) / $prev * 100);
            if ($change >= $threshold) {
                $dir = $info['rate'] > $prev ? '📈 ارتفع' : '📉 انخفض';
                $msg = "تنبيه سعر الصرف\n{$dir} سعر {$code} مقابل USD\n"
                     . "السابق: " . number_format($prev, 4) . "\n"
                     . "الجديد: " . number_format($info['rate'], 4) . "\n"
                     . "التغيير: " . number_format($change, 2) . "%";
                $url = "https://api.telegram.org/bot{$token}/sendMessage";
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => ['chat_id' => $chatId, 'text' => $msg],
                    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10
                ]);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }
}

/* ── قائمة الأسعار ── */
function getPriceList(PDO $pdo, string $category = ''): array {
    if ($category) {
        $stmt = $pdo->prepare("SELECT * FROM price_list WHERE is_active=1 AND category=? ORDER BY category, name");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->query("SELECT * FROM price_list WHERE is_active=1 ORDER BY category, name");
    }
    return $stmt->fetchAll();
}

function getPriceCategories(PDO $pdo): array {
    $stmt = $pdo->query("SELECT DISTINCT category FROM price_list WHERE is_active=1 ORDER BY category");
    return array_column($stmt->fetchAll(), 'category');
}

/* ── التحقق من reCAPTCHA/Turnstile ── */
function verifyCaptcha(array $settings, string $token): bool {
    $cfEnabled = ($settings['cf_turnstile_enabled'] ?? '0') === '1';
    $rcEnabled = ($settings['recaptcha_enabled'] ?? '0') === '1';

    if ($cfEnabled && !empty($settings['cf_turnstile_secret_key'])) {
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['secret' => $settings['cf_turnstile_secret_key'], 'response' => $token],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8
        ]);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return !empty($res['success']);
    }

    if ($rcEnabled && !empty($settings['recaptcha_secret_key'])) {
        $version = $settings['recaptcha_version'] ?? 'v2';
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['secret' => $settings['recaptcha_secret_key'], 'response' => $token],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8
        ]);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if ($version === 'v3') {
            return !empty($res['success']) && ($res['score'] ?? 0) >= 0.5;
        }
        return !empty($res['success']);
    }

    return true; // لا يوجد captcha مُفعَّل
}

/* ── نسخ احتياطي عبر تيليجرام ── */
function sendTelegramBackup(PDO $pdo, string $token, string $chatId): bool {
    if (!$token || !$chatId) return false;
    $tables = ['customers','transactions','settings'];
    $backup = "-- دفتر الدكان — نسخة احتياطية " . date('Y-m-d H:i:s') . "\n\n";
    foreach ($tables as $table) {
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) continue;
        $cols = array_keys($rows[0]);
        $backup .= "-- جدول: $table\n";
        foreach ($rows as $row) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : "'" . addslashes((string)$v) . "'", $row);
            $backup .= "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n";
        }
        $backup .= "\n";
    }

    $fileName = 'backup-' . date('Y-m-d') . '.sql';
    $tmpFile  = sys_get_temp_dir() . '/' . $fileName;
    file_put_contents($tmpFile, $backup);

    $url  = "https://api.telegram.org/bot{$token}/sendDocument";
    $data = [
        'chat_id'  => $chatId,
        'caption'  => "📦 نسخة احتياطية تلقائية — " . date('Y-m-d H:i'),
        'document' => new CURLFile($tmpFile, 'text/plain', $fileName),
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $data, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $result = curl_exec($ch);
    $ok     = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    @unlink($tmpFile);
    return $ok;
}
