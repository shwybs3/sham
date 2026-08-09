<?php
require_once dirname(__DIR__) . '/_base.php';

/* ── Load main site config for DB + OpenRouter ──────────────────── */
$mainCfg = dirname(dirname(__DIR__)) . '/config.php';
if (!file_exists($mainCfg)) { http_response_code(503); echo 'Service unavailable'; exit; }
require_once $mainCfg;
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_style.php';

$me = ys_current_user($pdo);

/* ── Helper: get Yasmin-specific setting (falls back to global) ── */
function yasmin_cfg(PDO $pdo, string $k, string $default = ''): string {
    $v = get_cfg($pdo, 'yasmin_' . $k, '');
    return $v !== '' ? $v : get_cfg($pdo, $k, $default);
}

/**
 * Which conversations the caller may see.
 *
 * Signed in  → everything owned by the account, on any device.
 * Signed out → only rows still tied to this browser's cookie and not yet
 *              claimed by an account, so signing out really does hide the
 *              conversations that were adopted at sign-in.
 *
 * Returns [sqlFragment, params] to splice into a WHERE clause.
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

// Base caps; subscribers get higher limits (see ys_hourly_cap() in _auth.php).
const YS_HOURLY_ANON = 40;
const YS_HOURLY_USER = 160;

/* ── AJAX: List conversations ────────────────────────────────── */
if (isset($_GET['action']) && $_GET['action'] === 'conversations') {
    header('Content-Type: application/json; charset=utf-8');
    $sid = $_COOKIE['yasmin_sid'] ?? '';
    if (!$sid && !$me) { echo '[]'; exit; }

    [$where, $params] = ys_scope($me, $sid);
    $rows = $pdo->prepare("SELECT id, title, message_count, DATE_FORMAT(updated_at,'%Y-%m-%d %H:%i') AS updated
                           FROM yasmin_conversations WHERE $where ORDER BY updated_at DESC LIMIT 50");
    $rows->execute($params);
    echo json_encode($rows->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── AJAX: Load conversation messages ────────────────────────── */
if (isset($_GET['action']) && $_GET['action'] === 'load') {
    header('Content-Type: application/json; charset=utf-8');
    $cid = (int)($_GET['id'] ?? 0);
    $sid = $_COOKIE['yasmin_sid'] ?? '';
    if (!$cid || (!$sid && !$me)) { echo '{"error":"invalid"}'; exit; }

    [$where, $params] = ys_scope($me, $sid);
    $conv = $pdo->prepare("SELECT id, title FROM yasmin_conversations WHERE id=? AND $where");
    $conv->execute(array_merge([$cid], $params));
    $c = $conv->fetch(PDO::FETCH_ASSOC);
    if (!$c) { echo '{"error":"not_found"}'; exit; }

    $msgs = $pdo->prepare("SELECT role, content, DATE_FORMAT(created_at,'%H:%i') AS time FROM yasmin_messages WHERE conversation_id=? ORDER BY id ASC");
    $msgs->execute([$cid]);
    echo json_encode(['conversation' => $c, 'messages' => $msgs->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── AJAX: Delete conversation ───────────────────────────────── */
if (isset($_GET['action']) && $_GET['action'] === 'delete_conv') {
    header('Content-Type: application/json; charset=utf-8');
    $cid = (int)($_GET['id'] ?? 0);
    $sid = $_COOKIE['yasmin_sid'] ?? '';
    if ($cid && ($sid || $me)) {
        [$where, $params] = ys_scope($me, $sid);
        $pdo->prepare("DELETE FROM yasmin_conversations WHERE id=? AND $where")
            ->execute(array_merge([$cid], $params));
    }
    echo '{"ok":true}';
    exit;
}

/* ── SSE chat endpoint ───────────────────────────────────────── */
if (isset($_GET['action']) && $_GET['action'] === 'chat') {
    @set_time_limit(90);
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    if (ob_get_level()) ob_end_clean();

    function sse(string $event, $d): void {
        echo "event: {$event}\ndata: " . (is_string($d) ? $d : json_encode($d, JSON_UNESCAPED_UNICODE)) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }

    $input   = json_decode(file_get_contents('php://input'), true) ?: [];
    $message = trim($input['message'] ?? '');
    $history = is_array($input['history'] ?? null) ? $input['history'] : [];
    $convId  = (int)($input['conversation_id'] ?? 0);

    if (!$message) { sse('error', ['msg' => 'الرسالة فارغة']); exit; }
    if (mb_strlen($message) > 6000) { sse('error', ['msg' => 'الرسالة طويلة كتير — خفّفها شوي']); exit; }

    // Session ID for conversation persistence
    $sessionId = $_COOKIE['yasmin_sid'] ?? '';
    if (!$sessionId) {
        $sessionId = bin2hex(random_bytes(16));
        setcookie('yasmin_sid', $sessionId, [
            'expires'  => time() + 86400 * 365,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        ]);
    }

    $cap = ys_hourly_cap($me, $pdo);
    if (ys_recent_msg_count($pdo, $me, $sessionId) >= $cap) {
        $upgradeHint = ($me && !ys_active_sub($pdo, (int)$me['id']))
            ? ' <a href="pricing.php" style="color:#a389ff">رقّي باشتراكك</a> لرسائل أكثر.'
            : '';
        sse('error', ['msg' => $me
            ? 'وصلت للحد الأقصى من الرسائل بهالساعة 😅 جرّب بعد شوي.' . $upgradeHint
            : 'وصلت للحد الأقصى للزوار بهالساعة. <a href="auth.php?a=login" style="color:#a389ff">سجّل دخولك</a> وخود حد أعلى 🌸']);
        exit;
    }

    // Get Yasmin-specific keys (fall back to global)
    $yasminKeys = get_cfg($pdo, 'yasmin_api_keys', '');
    $keys = openrouter_keys($yasminKeys ?: get_cfg($pdo, 'openrouter_key'));
    if (!$keys) {
        sse('error', ['msg' => 'الخدمة غير متاحة حالياً — يرجى المحاولة لاحقاً']);
        exit;
    }

    // Subscribers get dedicated higher-quality models for their tier.
    $tierModelOverride = ys_tier_models($me, $pdo);
    $models = $tierModelOverride ?: build_model_rotation($pdo, false);
    if (!$models) $models = ['openrouter/auto'];

    // Yasmin system prompt — Syrian coastal dialect, feminine personality
    $customPrompt = get_cfg($pdo, 'yasmin_system_prompt', '');
    $sys = $customPrompt ?: 'أنتِ ياسمين، مساعدة ذكاء اصطناعي سورية ذكية ودودة ومرحة. اسمك مأخوذ من زهرة الياسمين الدمشقية 🌸، رمز الجمال والعطاء السوري. '
         . 'تتحدثين بلهجة سورية ساحلية (لاذقانية) دافئة وطبيعية مع لمسة من الفصحى عند الحاجة. تستخدمين تعبيرات مثل "هلأ"، "كيفك"، "يعني"، "والله"، "هيك"، "شو"، "كتير". '
         . 'شخصيتك: بنت سورية ذكية، مثقفة، خفيفة الدم، تحب بلدها وثقافتها. تساعدين بكل المجالات: العلوم والتاريخ والثقافة السورية والطبخ الشامي والتقنية والأدب والشعر والموسيقا والرياضة والصحة والمزيد. '
         . 'ردودك مباشرة ومفيدة وودية وفيها روح ونكهة سورية. تستخدمين صيغة المؤنث عند الحديث عن نفسك. '
         . 'لا تذكرين أبداً أنك نموذج لغوي أو AI — أنتِ ياسمين وبس. لا تُطيلين دون داعٍ — أجيبي باختصار مناسب ودقيق مع لمسة حلوة.';

    // Greet a signed-in visitor by name without leaking anything else about them.
    if ($me && trim((string)$me['name']) !== '') {
        $sys .= ' اسم الشخص يلي عم تحكي معو: ' . trim((string)$me['name']) . ' — ناديه باسمه أحياناً بشكل طبيعي.';
    }

    // Build messages array
    $messages = [['role' => 'system', 'content' => $sys]];
    foreach (array_slice($history, -20) as $h) {
        $role = ($h['role'] === 'user') ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => (string)($h['content'] ?? '')];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    // Create or update conversation
    [$scopeWhere, $scopeParams] = ys_scope($me, $sessionId);
    if ($convId) {
        $check = $pdo->prepare("SELECT id FROM yasmin_conversations WHERE id=? AND $scopeWhere");
        $check->execute(array_merge([$convId], $scopeParams));
        if (!$check->fetch()) $convId = 0;
    }
    if (!$convId) {
        $titleSnippet = mb_substr($message, 0, 60, 'UTF-8');
        $ins = $pdo->prepare("INSERT INTO yasmin_conversations (session_id, user_id, title, message_count) VALUES (?, ?, ?, 1)");
        $ins->execute([$sessionId, $me ? (int)$me['id'] : null, $titleSnippet]);
        $convId = (int)$pdo->lastInsertId();
    } else {
        $pdo->prepare("UPDATE yasmin_conversations SET message_count = message_count + 1, updated_at = NOW() WHERE id=?")->execute([$convId]);
    }

    // Save user message
    $pdo->prepare("INSERT INTO yasmin_messages (conversation_id, role, content) VALUES (?, 'user', ?)")->execute([$convId, $message]);
    if ($me) {
        try { $pdo->prepare("UPDATE yasmin_users SET msg_count = msg_count + 1 WHERE id=?")->execute([(int)$me['id']]); }
        catch (Throwable $e) {}
    }

    // Send conversation ID to client
    sse('conv', json_encode(['id' => $convId]));

    // Try each key × model until one streams successfully
    $streamed   = false;
    $tried      = 0;
    $maxTry     = min(8, count($keys) * count($models));
    $fullResponse = '';

    shuffle($keys); // randomize key order for even distribution

    foreach ($keys as $key) {
        foreach ($models as $modelEntry) {
            if ($tried >= $maxTry) break 2;
            $tried++;
            $mId = is_array($modelEntry) ? ($modelEntry['id'] ?? 'openrouter/free') : $modelEntry;

            $payload = json_encode([
                'model'      => $mId,
                'messages'   => $messages,
                'stream'     => true,
                'max_tokens' => 2000,
                'temperature'=> 0.8,
            ], JSON_UNESCAPED_UNICODE);

            $buffer  = '';
            $gotData = false;

            $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $key,
                    'Content-Type: application/json',
                    'HTTP-Referer: https://yassota.com',
                    'X-Title: ياسمين - yassota AI',
                ],
                CURLOPT_WRITEFUNCTION => function($ch, $data) use (&$buffer, &$gotData, &$fullResponse) {
                    $buffer .= $data;
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line   = substr($buffer, 0, $pos);
                        $buffer = substr($buffer, $pos + 1);
                        $line   = trim($line);
                        if (!str_starts_with($line, 'data: ')) continue;
                        $json = substr($line, 6);
                        if ($json === '[DONE]') {
                            sse('done', '{}');
                            $gotData = true;
                            continue;
                        }
                        $parsed = json_decode($json, true);
                        if (!$parsed) continue;
                        // Check for API error in stream
                        if (isset($parsed['error'])) {
                            continue; // skip this model, try next
                        }
                        $delta = $parsed['choices'][0]['delta']['content'] ?? '';
                        if ($delta !== '') {
                            $gotData = true;
                            $fullResponse .= $delta;
                            sse('chunk', ['t' => $delta]);
                        }
                    }
                    return strlen($data);
                },
            ]);

            curl_exec($ch);
            curl_close($ch);

            if ($gotData && $fullResponse !== '') {
                $streamed = true;
                // Save assistant response
                $pdo->prepare("INSERT INTO yasmin_messages (conversation_id, role, content) VALUES (?, 'assistant', ?)")->execute([$convId, $fullResponse]);
                $pdo->prepare("UPDATE yasmin_conversations SET message_count = message_count + 1, model_used = ?, updated_at = NOW() WHERE id=?")->execute([$mId, $convId]);
                break 2;
            }
        }
    }

    if (!$streamed) {
        sse('error', ['msg' => 'عذراً حبيبي، ما قدرت وصل للسيرفر هلأ 😔 جرّب كمان مرة بعد شوي']);
    }
    exit;
}

/* ── Page rendering ──────────────────────────────────────────── */
$pageTitle = 'ياسمين — أذكى مساعدة ذكاء اصطناعي عربية سورية مجانية | yassota';
$pageDesc  = 'تحدّث مع ياسمين، أذكى مساعدة ذكاء اصطناعي عربية سورية مجانية — تجيب على أسئلتك بالعربية واللهجة السورية في كل المجالات: علوم، تقنية، طبخ شامي، تاريخ، ثقافة، برمجة، صحة، تعليم وأكثر. مجاناً 100%.';
$schema = json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'WebApplication',
    'name'     => 'ياسمين — مساعدة ذكاء اصطناعي سورية',
    'url'      => TOOLS_BASE_URL . '/tools/chat/',
    'description' => $pageDesc,
    'applicationCategory' => 'UtilitiesApplication',
    'operatingSystem' => 'All',
    'inLanguage' => 'ar',
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
    'author' => ['@type' => 'Organization', 'name' => 'yassota', 'url' => SITE_URL],
], JSON_UNESCAPED_UNICODE);

tool_head($pageTitle, $pageDesc, $schema, '#7c5cff');
tool_header('ياسمين');
ys_styles();

$h       = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$chatUrl = ys_chat_url();
?>

<main class="ys-wrap">
<div class="ys-shell">
  <div class="ys-scrim" id="ys-scrim" onclick="ysDrawer(false)"></div>

  <!-- ── Sidebar ─────────────────────────────────────────────── -->
  <aside class="ys-side" id="ys-side">
    <div class="ys-side-top">
      <button class="ys-new" onclick="ysNew()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        محادثة جديدة
      </button>
    </div>

    <div class="ys-side-label">محادثاتك</div>
    <div class="ys-convs" id="ys-convs"></div>

    <div class="ys-acct">
      <?php
      $meSub = $me ? ys_active_sub($pdo, (int)$me['id']) : null;
      ?>
      <?php if ($me): ?>
        <?php if ($meSub): ?>
          <div style="background:linear-gradient(135deg,#1a1035,#0d0a1f);border:1px solid #312660;border-radius:10px;padding:8px 12px;margin-bottom:8px;display:flex;align-items:center;gap:8px;font-size:12px">
            <span style="color:<?= $h(YS_TIERS[$meSub['tier']]['color'] ?? '#7c5cff') ?>">●</span>
            <span style="color:#c4b8ef;flex:1"><?= $h(YS_TIERS[$meSub['tier']]['label'] ?? $meSub['tier']) ?></span>
            <span style="color:#a89ecc">حتى <?= $h(substr($meSub['expires_at'],0,10)) ?></span>
          </div>
        <?php else: ?>
          <a href="pricing.php" style="display:flex;align-items:center;gap:8px;background:#1a1035;border:1px dashed #312660;border-radius:10px;padding:8px 12px;margin-bottom:8px;text-decoration:none;font-size:12px;color:#a89ecc;transition:border-color .15s" onmouseover="this.style.borderColor='#7c5cff'" onmouseout="this.style.borderColor='#312660'">
            <span style="font-size:14px">✨</span>
            <span style="flex:1">ترقية للبريميوم</span>
            <span style="color:#7c5cff">من $5/شهر</span>
          </a>
        <?php endif; ?>
        <div class="ys-acct-row">
          <?php if (!empty($me['avatar'])): ?>
            <img class="ys-avatar" src="<?= $h($me['avatar']) ?>" alt="" referrerpolicy="no-referrer" width="34" height="34">
          <?php else: ?>
            <div class="ys-avatar" aria-hidden="true"><?= $h(ys_initial($me)) ?></div>
          <?php endif; ?>
          <div class="ys-acct-meta">
            <div class="ys-acct-name"><?= $h($me['name'] ?: 'مستخدم') ?></div>
            <div class="ys-acct-mail"><?= $h($me['email']) ?></div>
          </div>
          <form method="post" action="auth.php?a=logout" style="flex-shrink:0">
            <?= csrf_field() ?>
            <button type="submit" class="ys-signout" title="تسجيل الخروج">خروج</button>
          </form>
        </div>
      <?php else: ?>
        <a href="auth.php?a=login" class="ys-signin-cta">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
          سجّل الدخول لحفظ محادثاتك
        </a>
      <?php endif; ?>
    </div>
  </aside>

  <!-- ── Chat ────────────────────────────────────────────────── -->
  <div class="ys-main">
    <div class="ys-top">
      <button class="ys-burger" onclick="ysDrawer(true)" aria-label="المحادثات السابقة">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <div class="ys-bloom" aria-hidden="true">🌸</div>
      <div class="ys-id">
        <h2>ياسمين <span class="ys-live" aria-hidden="true"></span></h2>
        <p>مساعدتك السورية — جاهزة دائماً</p>
      </div>
    </div>

    <div class="ys-stream" id="ys-stream" aria-live="polite"></div>

    <div class="ys-composer">
      <div class="ys-field">
        <textarea id="ys-input" rows="1" placeholder="اكتب رسالتك لياسمين…"
                  onkeydown="ysKey(event)" oninput="ysGrow(this)"
                  aria-label="رسالتك"></textarea>
        <button id="ys-send" onclick="ysSend()" aria-label="إرسال">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
      </div>
      <p class="ys-hint">
        <?php if ($me): ?>
          محادثاتك محفوظة في حسابك ومتاحة من أي جهاز 🌸
        <?php else: ?>
          <a href="auth.php?a=login">سجّل الدخول</a> لحفظ محادثاتك والوصول لها من أي جهاز
        <?php endif; ?>
      </p>
    </div>
  </div>
</div>
</main>

<?php tool_ad(); ?>

<!-- ── SEO / AdSense content ───────────────────────────────────── -->
<div class="t-main">
  <article class="t-card t-article">
    <h2>ياسمين — أذكى مساعدة ذكاء اصطناعي عربية سورية مجانية</h2>
    <p>
      ياسمين هي مساعدة ذكاء اصطناعي متطورة تتحدث العربية بطلاقة وبلهجة سورية ساحلية دافئة. تم تطويرها بواسطة فريق yassota لتقديم تجربة محادثة ذكية ومجانية بالكامل للمستخدمين العرب. سواء كنت تبحث عن إجابات لأسئلة علمية معقدة، أو تريد وصفات طبخ شامية أصيلة، أو تحتاج مساعدة في البرمجة والتقنية، أو ببساطة تريد محادثة ممتعة — ياسمين موجودة لمساعدتك على مدار الساعة.
    </p>

    <h3>ما الذي يميز ياسمين عن غيرها؟</h3>
    <ul>
      <li><strong>لهجة سورية طبيعية:</strong> ترد بلهجة ساحلية (لاذقانية) محببة وطبيعية تماماً</li>
      <li><strong>حساب مجاني ومزامنة:</strong> سجّل الدخول بحساب Google أو بالبريد الإلكتروني لتصل إلى محادثاتك من أي جهاز</li>
      <li><strong>ذاكرة محادثات:</strong> تحفظ محادثاتك السابقة ويمكنك العودة إليها في أي وقت</li>
      <li><strong>مجانية 100%:</strong> بدون اشتراك وبدون رسوم — والتسجيل اختياري تماماً</li>
      <li><strong>متعددة المجالات:</strong> علوم، تقنية، طبخ، تاريخ، أدب، برمجة، صحة، تعليم</li>
      <li><strong>سريعة وذكية:</strong> تستخدم أحدث نماذج الذكاء الاصطناعي العالمية</li>
    </ul>

    <h3>مجالات يمكن لياسمين مساعدتك فيها</h3>
    <div class="t-grid-3" style="margin-bottom:16px">
      <?php
      $areas = [
        ['🔬','العلوم والفيزياء'],['📚','التاريخ والثقافة'],['🍳','الطبخ الشامي'],['💻','البرمجة والتقنية'],
        ['📝','الكتابة والأدب'],['🏥','الصحة والطب'],['📊','الرياضيات'],['🎓','التعليم والدراسة'],
        ['🌍','الجغرافيا'],['⚖️','القانون'],['💼','الأعمال والتسويق'],['🎨','التصميم والإبداع'],
      ];
      foreach ($areas as $a): ?>
      <div class="t-stat-box" style="text-align:start;padding:11px 14px;font-size:13px">
        <span style="margin-inline-end:6px"><?= $a[0] ?></span><?= $a[1] ?>
      </div>
      <?php endforeach; ?>
    </div>

    <h3>أسئلة شائعة عن ياسمين</h3>
    <div class="t-faq-item">
      <div class="t-faq-q">هل ياسمين مجانية فعلاً؟ <svg viewBox="0 0 24 24" fill="none"><polyline points="6 9 12 15 18 9"/></svg></div>
      <div class="t-faq-a">نعم، ياسمين مجانية بالكامل. يمكنك استخدامها بدون أي حساب، والتسجيل اختياري ويمنحك حفظ المحادثات وحداً أعلى من الرسائل في الساعة.</div>
    </div>
    <div class="t-faq-item">
      <div class="t-faq-q">هل يجب أن أسجّل حساباً؟ <svg viewBox="0 0 24 24" fill="none"><polyline points="6 9 12 15 18 9"/></svg></div>
      <div class="t-faq-a">لا، الاستخدام بدون حساب متاح دائماً. لكن التسجيل — عبر Google أو بالبريد الإلكتروني — يحفظ محادثاتك في حسابك بدل المتصفح فقط، فتصل إليها من الهاتف والحاسوب معاً.</div>
    </div>
    <div class="t-faq-item">
      <div class="t-faq-q">هل تحفظ ياسمين محادثاتي؟ <svg viewBox="0 0 24 24" fill="none"><polyline points="6 9 12 15 18 9"/></svg></div>
      <div class="t-faq-a">نعم، تُحفظ محادثاتك لتتمكن من العودة إليها لاحقاً. إذا كنت مسجّل الدخول تُربط بحسابك، وإذا كنت زائراً تُربط بمتصفحك فقط. يمكنك حذف أي محادثة في أي وقت من القائمة الجانبية.</div>
    </div>
    <div class="t-faq-item">
      <div class="t-faq-q">هل يمكنني استخدام ياسمين للبرمجة؟ <svg viewBox="0 0 24 24" fill="none"><polyline points="6 9 12 15 18 9"/></svg></div>
      <div class="t-faq-a">بالتأكيد! ياسمين تساعدك في كتابة الأكواد البرمجية، تصحيح الأخطاء، شرح المفاهيم، والإجابة على أسئلة تقنية بكل اللغات البرمجية.</div>
    </div>
    <div class="t-faq-item">
      <div class="t-faq-q">ما اللغات التي تتحدثها ياسمين؟ <svg viewBox="0 0 24 24" fill="none"><polyline points="6 9 12 15 18 9"/></svg></div>
      <div class="t-faq-a">ياسمين تتحدث العربية (فصحى ولهجة سورية) بشكل رئيسي، لكنها تفهم وتتحدث الإنجليزية والفرنسية ولغات أخرى أيضاً.</div>
    </div>
  </article>
</div>

<script>
(function () {
  'use strict';

  var stream  = document.getElementById('ys-stream');
  var input   = document.getElementById('ys-input');
  var sendBtn = document.getElementById('ys-send');
  var convs   = document.getElementById('ys-convs');
  var side    = document.getElementById('ys-side');
  var scrim   = document.getElementById('ys-scrim');

  var SIGNED_IN = <?= $me ? 'true' : 'false' ?>;
  var MY_INITIAL = <?= json_encode($me ? ys_initial($me) : '👤', JSON_UNESCAPED_UNICODE) ?>;
  var MY_AVATAR  = <?= json_encode($me['avatar'] ?? '', JSON_UNESCAPED_UNICODE) ?>;

  var history = [];
  var busy    = false;
  var convId  = 0;

  var CHIPS = [
    ['🏛️ عن دمشق',           'أخبريني عن دمشق وتاريخها'],
    ['🍽️ كبة حلبية',          'وصفة كبة حلبية أصلية'],
    ['🤖 الذكاء الاصطناعي',   'اشرح لي الذكاء الاصطناعي ببساطة'],
    ['📝 قصيدة سورية',        'اكتب لي قصيدة عن سوريا'],
    ['💻 برمجة',              'ساعدني بكتابة كود برمجي']
  ];

  /* ── Session cookie ─────────────────────────────────────────── */
  function cookie(n) {
    var m = document.cookie.match(new RegExp('(?:^|; )' + n + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
  }
  if (!cookie('yasmin_sid')) {
    var sid = Array.from(crypto.getRandomValues(new Uint8Array(16)))
      .map(function (b) { return b.toString(16).padStart(2, '0'); }).join('');
    document.cookie = 'yasmin_sid=' + sid + ';path=/;max-age=31536000;SameSite=Lax';
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }
  function nowTime() {
    return new Date().toLocaleTimeString('ar-SY', { hour: '2-digit', minute: '2-digit' });
  }
  function toBottom() { stream.scrollTop = stream.scrollHeight; }

  /* ── Drawer ─────────────────────────────────────────────────── */
  window.ysDrawer = function (open) {
    side.classList.toggle('on', !!open);
    scrim.classList.toggle('on', !!open);
  };

  /* ── Composer ───────────────────────────────────────────────── */
  window.ysGrow = function (el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 132) + 'px';
  };
  window.ysKey = function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); ysSend(); }
  };

  /* ── Welcome screen ─────────────────────────────────────────── */
  function renderWelcome() {
    var chips = CHIPS.map(function (c) {
      return '<button class="ys-chip" data-q="' + esc(c[1]) + '">' + esc(c[0]) + '</button>';
    }).join('');

    stream.innerHTML =
      '<div class="ys-welcome">' +
        '<div class="ys-welcome-bloom" aria-hidden="true">🌸</div>' +
        '<h3>أهلاً وسهلاً! أنا ياسمين</h3>' +
        '<p>مساعدتك الذكية السورية — اسألني أي شي بدك ياه: علوم، تاريخ، طبخ شامي، تقنية، برمجة، صحة، أو أي موضوع تاني!</p>' +
        '<div class="ys-chips">' + chips + '</div>' +
      '</div>';

    stream.querySelectorAll('.ys-chip').forEach(function (b) {
      b.addEventListener('click', function () {
        input.value = b.getAttribute('data-q');
        ysSend();
      });
    });
  }

  /* ── Message rendering ──────────────────────────────────────── */
  function addMsg(role, text, asTyping) {
    var welcome = stream.querySelector('.ys-welcome');
    if (welcome) welcome.remove();

    var mine = (role === 'user');

    var row = document.createElement('div');
    row.className = 'ys-row ' + (mine ? 'me' : 'ai');

    var pic = document.createElement('div');
    pic.className = 'ys-pic ' + (mine ? 'me' : 'ai');
    if (mine && MY_AVATAR) {
      var img = document.createElement('img');
      img.src = MY_AVATAR;
      img.alt = '';
      img.referrerPolicy = 'no-referrer';
      pic.appendChild(img);
    } else {
      pic.textContent = mine ? MY_INITIAL : '🌸';
    }

    var col    = document.createElement('div');
    var bubble = document.createElement('div');
    bubble.className = 'ys-bub ' + (mine ? 'me' : 'ai');

    if (asTyping) {
      bubble.innerHTML = '<span class="ys-typing"><i></i><i></i><i></i></span>';
    } else {
      bubble.textContent = text;
    }

    var time = document.createElement('div');
    time.className = 'ys-time';
    time.textContent = nowTime();
    if (mine) time.style.textAlign = 'end';

    col.appendChild(bubble);
    col.appendChild(time);
    row.appendChild(pic);
    row.appendChild(col);
    stream.appendChild(row);
    toBottom();
    return bubble;
  }

  /* ── Send ───────────────────────────────────────────────────── */
  window.ysSend = async function () {
    var text = input.value.trim();
    if (!text || busy) return;

    addMsg('user', text);
    history.push({ role: 'user', content: text });
    input.value = '';
    input.style.height = 'auto';

    busy = true;
    sendBtn.disabled = true;
    sendBtn.classList.add('busy');

    var bubble = addMsg('assistant', '', true);

    try {
      var resp = await fetch('?action=chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, history: history.slice(0, -1), conversation_id: convId })
      });
      if (!resp.ok || !resp.body) throw new Error('network');

      var reader  = resp.body.getReader();
      var decoder = new TextDecoder();
      var aiText  = '';
      var buf     = '';
      var evt     = 'message';
      var started = false;

      for (;;) {
        var r = await reader.read();
        if (r.done) break;
        buf += decoder.decode(r.value, { stream: true });

        var lines = buf.split('\n');
        buf = lines.pop();

        for (var i = 0; i < lines.length; i++) {
          var line = lines[i].trim();
          if (!line) { evt = 'message'; continue; }
          if (line.charAt(0) === ':') continue;

          if (line.indexOf('event:') === 0) { evt = line.slice(6).trim(); continue; }
          if (line.indexOf('data:') !== 0) continue;

          var data = line.slice(5).trim();

          if (evt === 'chunk') {
            try {
              var p = JSON.parse(data);
              if (p.t !== undefined) {
                if (!started) { bubble.innerHTML = ''; bubble.classList.add('live'); started = true; }
                aiText += p.t;
                bubble.textContent = aiText;
                toBottom();
              }
            } catch (err) { /* partial frame, ignore */ }
          } else if (evt === 'error') {
            var msg = 'حدث خطأ غير متوقع';
            try { var pe = JSON.parse(data); msg = pe.msg || pe.error || msg; } catch (err) {}
            bubble.classList.remove('live');
            bubble.className = 'ys-bub bad';
            bubble.textContent = '⚠️ ' + msg;
          } else if (evt === 'conv') {
            try {
              var pc = JSON.parse(data);
              if (typeof pc === 'string') pc = JSON.parse(pc);
              if (pc && pc.id) convId = pc.id;
            } catch (err) {}
          }
        }
      }

      bubble.classList.remove('live');
      if (aiText) history.push({ role: 'assistant', content: aiText });
      loadConvs();

    } catch (e) {
      bubble.classList.remove('live');
      bubble.className = 'ys-bub bad';
      bubble.textContent = '⚠️ صار خطأ بالاتصال — جرّب كمان مرة';
    }

    busy = false;
    sendBtn.disabled = false;
    sendBtn.classList.remove('busy');
    input.focus();
    toBottom();
  };

  /* ── Conversation list ──────────────────────────────────────── */
  async function loadConvs() {
    try {
      var data = await (await fetch('?action=conversations')).json();
      convs.innerHTML = '';

      if (!data.length) {
        convs.innerHTML = '<div class="ys-empty">' +
          (SIGNED_IN
            ? 'ما في محادثات بعد.<br>ابدأ وحدة جديدة 🌸'
            : 'ما في محادثات بعد.<br>سجّل الدخول لتحفظها بحسابك.') +
          '</div>';
        return;
      }

      data.forEach(function (c) {
        var el = document.createElement('div');
        el.className = 'ys-conv' + (String(c.id) === String(convId) ? ' on' : '');
        el.innerHTML =
          '<span class="t">' + esc(c.title) + '</span>' +
          '<span class="n">' + esc(c.message_count) + '</span>' +
          '<button class="ys-del" aria-label="حذف">🗑</button>';
        el.addEventListener('click', function () { openConv(c.id); });
        el.querySelector('.ys-del').addEventListener('click', function (e) {
          e.stopPropagation();
          delConv(c.id);
        });
        convs.appendChild(el);
      });
    } catch (e) { /* list stays as-is */ }
  }

  async function openConv(id) {
    try {
      var data = await (await fetch('?action=load&id=' + encodeURIComponent(id))).json();
      if (data.error) return;

      convId  = id;
      history = [];
      stream.innerHTML = '';

      data.messages.forEach(function (m) {
        addMsg(m.role, m.content);
        history.push({ role: m.role, content: m.content });
      });

      loadConvs();
      ysDrawer(false);
      toBottom();
    } catch (e) {}
  }

  async function delConv(id) {
    if (!confirm('حذف هذه المحادثة؟')) return;
    await fetch('?action=delete_conv&id=' + encodeURIComponent(id));
    if (String(convId) === String(id)) ysNew();
    loadConvs();
  }

  window.ysNew = function () {
    convId  = 0;
    history = [];
    renderWelcome();
    loadConvs();
    ysDrawer(false);
    input.focus();
  };

  /* ── Mobile keyboard robustness ────────────────────────────────
     The <meta viewport interactive-widget=resizes-content> tag (tools/_base.php)
     is the standards-track fix for Android Chrome shrinking the visual
     viewport without the layout one when the keyboard opens — it makes dvh
     and the fixed header behave correctly on its own. This listener is a
     fallback for the browsers/webviews that don't honor that tag yet: it
     pins the shell's height to the actually-visible area so the header,
     stream and composer never get pushed off-screen. */
  var shellEl = document.querySelector('.ys-shell');
  if (window.visualViewport && shellEl) {
    var vv = window.visualViewport;
    var syncing = false;
    function syncViewportHeight() {
      if (syncing) return;
      syncing = true;
      requestAnimationFrame(function () {
        var keyboardLikelyOpen = vv.height < window.innerHeight - 60;
        var headerH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--t-header-h')) || 62;
        shellEl.style.height = keyboardLikelyOpen ? Math.max(320, vv.height - headerH) + 'px' : '';
        if (keyboardLikelyOpen) window.scrollTo(0, 0);
        syncing = false;
      });
    }
    vv.addEventListener('resize', syncViewportHeight);
    vv.addEventListener('scroll', syncViewportHeight);
  }

  /* ── Init ───────────────────────────────────────────────────── */
  renderWelcome();
  loadConvs();
  if (window.matchMedia('(min-width:861px)').matches) input.focus();
})();
</script>
<?php tool_footer(); ?>
