<?php
require_once dirname(__DIR__) . '/_base.php';

/* ── Load main site config for DB + OpenRouter ──────────────────── */
$mainCfg = dirname(dirname(__DIR__)) . '/config.php';
if (!file_exists($mainCfg)) { http_response_code(503); echo 'Service unavailable'; exit; }
require_once $mainCfg;

/* ── Helper: get Yasmin-specific setting (falls back to global) ── */
function yasmin_cfg(PDO $pdo, string $k, string $default = ''): string {
    $v = get_cfg($pdo, 'yasmin_' . $k, '');
    return $v !== '' ? $v : get_cfg($pdo, $k, $default);
}

/* ── AJAX: List conversations ────────────────────────────────── */
if (isset($_GET['action']) && $_GET['action'] === 'conversations') {
    header('Content-Type: application/json; charset=utf-8');
    $sid = $_COOKIE['yasmin_sid'] ?? '';
    if (!$sid) { echo '[]'; exit; }
    $rows = $pdo->prepare("SELECT id, title, message_count, DATE_FORMAT(updated_at,'%Y-%m-%d %H:%i') AS updated
                           FROM yasmin_conversations WHERE session_id=? ORDER BY updated_at DESC LIMIT 50");
    $rows->execute([$sid]);
    echo json_encode($rows->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── AJAX: Load conversation messages ────────────────────────── */
if (isset($_GET['action']) && $_GET['action'] === 'load') {
    header('Content-Type: application/json; charset=utf-8');
    $cid = (int)($_GET['id'] ?? 0);
    $sid = $_COOKIE['yasmin_sid'] ?? '';
    if (!$cid || !$sid) { echo '{"error":"invalid"}'; exit; }
    $conv = $pdo->prepare("SELECT id, title FROM yasmin_conversations WHERE id=? AND session_id=?");
    $conv->execute([$cid, $sid]);
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
    if ($cid && $sid) {
        $pdo->prepare("DELETE FROM yasmin_conversations WHERE id=? AND session_id=?")->execute([$cid, $sid]);
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

    // Session ID for conversation persistence
    $sessionId = $_COOKIE['yasmin_sid'] ?? '';
    if (!$sessionId) {
        $sessionId = bin2hex(random_bytes(16));
        setcookie('yasmin_sid', $sessionId, time() + 86400 * 365, '/', '', false, true);
    }

    // Get Yasmin-specific keys (fall back to global)
    $yasminKeys = get_cfg($pdo, 'yasmin_api_keys', '');
    $keys = openrouter_keys($yasminKeys ?: get_cfg($pdo, 'openrouter_key'));
    if (!$keys) {
        sse('error', ['msg' => 'الخدمة غير متاحة حالياً — يرجى المحاولة لاحقاً']);
        exit;
    }

    $models = build_model_rotation($pdo, false);
    if (!$models) $models = ['openrouter/free'];

    // Yasmin system prompt — Syrian coastal dialect, feminine personality
    $customPrompt = get_cfg($pdo, 'yasmin_system_prompt', '');
    $sys = $customPrompt ?: 'أنتِ ياسمين، مساعدة ذكاء اصطناعي سورية ذكية ودودة ومرحة. اسمك مأخوذ من زهرة الياسمين الدمشقية 🌸، رمز الجمال والعطاء السوري. '
         . 'تتحدثين بلهجة سورية ساحلية (لاذقانية) دافئة وطبيعية مع لمسة من الفصحى عند الحاجة. تستخدمين تعبيرات مثل "هلأ"، "كيفك"، "يعني"، "والله"، "هيك"، "شو"، "كتير". '
         . 'شخصيتك: بنت سورية ذكية، مثقفة، خفيفة الدم، تحب بلدها وثقافتها. تساعدين بكل المجالات: العلوم والتاريخ والثقافة السورية والطبخ الشامي والتقنية والأدب والشعر والموسيقا والرياضة والصحة والمزيد. '
         . 'ردودك مباشرة ومفيدة وودية وفيها روح ونكهة سورية. تستخدمين صيغة المؤنث عند الحديث عن نفسك. '
         . 'لا تذكرين أبداً أنك نموذج لغوي أو AI — أنتِ ياسمين وبس. لا تُطيلين دون داعٍ — أجيبي باختصار مناسب ودقيق مع لمسة حلوة.';

    // Build messages array
    $messages = [['role' => 'system', 'content' => $sys]];
    foreach (array_slice($history, -20) as $h) {
        $role = ($h['role'] === 'user') ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => (string)($h['content'] ?? '')];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    // Create or update conversation
    if ($convId) {
        $check = $pdo->prepare("SELECT id FROM yasmin_conversations WHERE id=? AND session_id=?");
        $check->execute([$convId, $sessionId]);
        if (!$check->fetch()) $convId = 0;
    }
    if (!$convId) {
        $titleSnippet = mb_substr($message, 0, 60, 'UTF-8');
        $ins = $pdo->prepare("INSERT INTO yasmin_conversations (session_id, title, message_count) VALUES (?, ?, 1)");
        $ins->execute([$sessionId, $titleSnippet]);
        $convId = (int)$pdo->lastInsertId();
    } else {
        $pdo->prepare("UPDATE yasmin_conversations SET message_count = message_count + 1, updated_at = NOW() WHERE id=?")->execute([$convId]);
    }

    // Save user message
    $pdo->prepare("INSERT INTO yasmin_messages (conversation_id, role, content) VALUES (?, 'user', ?)")->execute([$convId, $message]);

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
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
$pageDesc  = 'تحدّث مع ياسمين، أذكى مساعدة ذكاء اصطناعي عربية سورية مجانية — تجيب على أسئلتك بالعربية واللهجة السورية في كل المجالات: علوم، تقنية، طبخ شامي، تاريخ، ثقافة، برمجة، صحة، تعليم وأكثر. بدون تسجيل، مجاناً 100%.';
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
tool_head($pageTitle, $pageDesc, $schema, '#CE1126');
tool_header();
?>
<style>
:root {
  --sy-red: #CE1126;
  --sy-red-dark: #a80e1e;
  --sy-green: #007A3D;
  --chat-bg: #f8f5f0;
  --bubble-ai: #ffffff;
  --bubble-user: var(--sy-red);
  --sidebar-bg: #1a0a0f;
  --sidebar-text: #e2d5d5;
}
*, *::before, *::after { box-sizing: border-box; }

/* Layout */
.yasmin-app {
  max-width: 960px;
  margin: 0 auto;
  display: flex;
  height: calc(100vh - 56px - 48px);
  background: #fff;
  border-radius: 18px 18px 0 0;
  overflow: hidden;
  box-shadow: 0 -2px 40px rgba(0,0,0,.08);
}

/* Sidebar — conversation history */
.ys-sidebar {
  width: 260px;
  background: var(--sidebar-bg);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  border-left: 1px solid rgba(255,255,255,.06);
}
.ys-sidebar-header {
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.ys-sidebar-header h3 {
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  margin: 0;
}
.ys-new-chat {
  background: var(--sy-red);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: background .2s;
}
.ys-new-chat:hover { background: var(--sy-red-dark); }
.ys-conv-list {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
}
.ys-conv-list::-webkit-scrollbar { width: 4px; }
.ys-conv-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
.ys-conv-item {
  padding: 10px 12px;
  border-radius: 10px;
  cursor: pointer;
  margin-bottom: 4px;
  transition: background .15s;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.ys-conv-item:hover { background: rgba(255,255,255,.08); }
.ys-conv-item.active { background: rgba(206,17,38,.25); }
.ys-conv-item .title {
  font-size: 13px;
  color: var(--sidebar-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}
.ys-conv-item .meta {
  font-size: 10px;
  color: rgba(255,255,255,.35);
  white-space: nowrap;
}
.ys-conv-delete {
  opacity: 0;
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-size: 14px;
  padding: 2px 4px;
  border-radius: 4px;
  transition: opacity .15s;
}
.ys-conv-item:hover .ys-conv-delete { opacity: 1; }

/* Main chat area */
.ys-chat {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

/* Chat header */
.chat-header {
  background: linear-gradient(135deg, var(--sy-red) 0%, #8b0000 100%);
  color: #fff;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}
.chat-avatar {
  width: 42px; height: 42px;
  background: rgba(255,255,255,.18);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
  border: 2px solid rgba(255,255,255,.35);
}
.chat-header-info { flex: 1; }
.chat-header-info h2 { font-size: 16px; font-weight: 800; margin: 0; }
.status-dot {
  width: 8px; height: 8px;
  background: #4ade80;
  border-radius: 50%;
  display: inline-block;
  margin-right: 4px;
  box-shadow: 0 0 6px #4ade80;
}
.ys-toggle-sidebar {
  display: none;
  background: rgba(255,255,255,.15);
  border: none;
  color: #fff;
  width: 36px; height: 36px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 18px;
  align-items: center;
  justify-content: center;
}

/* Messages */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  background: var(--chat-bg);
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  scroll-behavior: smooth;
}
.chat-messages::-webkit-scrollbar { width: 5px; }
.chat-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

.msg-row { display: flex; gap: 8px; align-items: flex-end; }
.msg-row.user { flex-direction: row-reverse; }
.msg-avatar {
  width: 30px; height: 30px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}
.msg-avatar.ai { background: linear-gradient(135deg, var(--sy-red), #8b0000); color: #fff; }
.msg-avatar.user { background: #e2e8f0; }
.bubble {
  max-width: 78%;
  padding: 10px 14px;
  border-radius: 16px;
  font-size: 14px;
  line-height: 1.75;
  white-space: pre-wrap;
  word-break: break-word;
}
.bubble.ai {
  background: var(--bubble-ai);
  color: #1e293b;
  border-radius: 4px 16px 16px 16px;
  box-shadow: 0 1px 8px rgba(0,0,0,.06);
  border: 1px solid #e8e0d8;
}
.bubble.user {
  background: var(--bubble-user);
  color: #fff;
  border-radius: 16px 4px 16px 16px;
}
.bubble.error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
  border-radius: 12px;
}
.bubble-time {
  font-size: 10px;
  color: #94a3b8;
  margin-top: 3px;
  padding: 0 4px;
}

/* Typing */
.typing-dots { display: flex; gap: 5px; align-items: center; padding: 4px 0; }
.typing-dots span {
  width: 7px; height: 7px;
  background: #94a3b8;
  border-radius: 50%;
  animation: blink 1.2s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: .2s; }
.typing-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes blink { 0%,80%,100%{opacity:.3} 40%{opacity:1} }

/* Welcome */
.welcome-banner {
  background: linear-gradient(135deg, #fff9f5, #fff);
  border: 1.5px solid #f5ddd0;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  gap: 14px;
  align-items: flex-start;
}
.welcome-banner .icon { font-size: 32px; flex-shrink: 0; }
.welcome-banner h3 { font-size: 16px; font-weight: 800; color: #7f1d1d; margin: 0 0 6px; }
.welcome-banner p { font-size: 13px; color: #64748b; line-height: 1.7; margin: 0; }
.quick-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
.quick-chip {
  background: #fff;
  border: 1.5px solid #f5caca;
  border-radius: 20px;
  padding: 6px 12px;
  font-size: 12px;
  cursor: pointer;
  color: var(--sy-red);
  font-weight: 600;
  font-family: inherit;
  transition: all .2s;
}
.quick-chip:hover { background: var(--sy-red); color: #fff; border-color: var(--sy-red); }

/* Input */
.chat-input-area {
  background: #fff;
  border-top: 1px solid #e8e0d8;
  padding: 12px 16px;
  display: flex;
  gap: 10px;
  align-items: flex-end;
  box-shadow: 0 -2px 12px rgba(0,0,0,.03);
}
#chat-input {
  flex: 1;
  border: 1.5px solid #e2e0dc;
  border-radius: 14px;
  padding: 10px 14px;
  font-size: 14px;
  font-family: inherit;
  resize: none;
  outline: none;
  direction: rtl;
  max-height: 120px;
  overflow-y: auto;
  line-height: 1.5;
  transition: border-color .2s;
  background: #faf8f5;
}
#chat-input:focus { border-color: var(--sy-red); background: #fff; }
#send-btn {
  width: 42px; height: 42px;
  background: var(--sy-red);
  border: none;
  border-radius: 12px;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: all .2s;
}
#send-btn:hover { background: var(--sy-red-dark); transform: scale(1.05); }
#send-btn:disabled { background: #94a3b8; transform: none; cursor: not-allowed; }
#send-btn svg { width: 18px; height: 18px; fill: #fff; }

.t-main { padding: 12px 12px 0; }

/* Mobile */
@media(max-width:700px) {
  .yasmin-app { height: calc(100dvh - 56px - 8px); border-radius: 0; }
  .ys-sidebar {
    position: fixed;
    top: 0; right: -280px;
    width: 280px; height: 100dvh;
    z-index: 1000;
    transition: right .3s ease;
  }
  .ys-sidebar.open { right: 0; }
  .ys-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 999;
  }
  .ys-sidebar-overlay.show { display: block; }
  .ys-toggle-sidebar { display: flex; }
  .bubble { max-width: 88%; }
  .t-main { padding: 4px 4px 0; }
  .chat-header { padding: 10px 14px; }
}
</style>

<main class="t-main">
<div class="yasmin-app">
  <!-- Sidebar overlay (mobile) -->
  <div class="ys-sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

  <!-- Conversation sidebar -->
  <div class="ys-sidebar" id="sidebar">
    <div class="ys-sidebar-header">
      <h3>💬 المحادثات</h3>
      <button class="ys-new-chat" onclick="newChat()">+ جديدة</button>
    </div>
    <div class="ys-conv-list" id="conv-list"></div>
  </div>

  <!-- Chat area -->
  <div class="ys-chat">
    <div class="chat-header">
      <button class="ys-toggle-sidebar" onclick="toggleSidebar()" title="المحادثات السابقة">📋</button>
      <div class="chat-avatar">🌸</div>
      <div class="chat-header-info">
        <h2><span class="status-dot"></span> ياسمين</h2>
      </div>
    </div>

    <div class="chat-messages" id="chat-messages">
      <div class="welcome-banner" id="welcome">
        <span class="icon">🌸</span>
        <div>
          <h3>أهلاً وسهلاً! أنا ياسمين 🇸🇾</h3>
          <p>مساعدتك الذكية السورية — اسألني أي شي بدك ياه: علوم، تاريخ، طبخ شامي، تقنية، برمجة، صحة، أو أي موضوع تاني!</p>
          <div class="quick-chips">
            <button class="quick-chip" onclick="send('أخبريني عن دمشق وتاريخها')">🏛️ عن دمشق</button>
            <button class="quick-chip" onclick="send('وصفة كبة حلبية أصلية')">🍽️ كبة حلبية</button>
            <button class="quick-chip" onclick="send('اشرح لي الذكاء الاصطناعي ببساطة')">🤖 الذكاء الاصطناعي</button>
            <button class="quick-chip" onclick="send('اكتب لي قصيدة عن سوريا')">📝 قصيدة سورية</button>
            <button class="quick-chip" onclick="send('ساعدني بكتابة كود برمجي')">💻 برمجة</button>
          </div>
        </div>
      </div>
    </div>

    <div class="chat-input-area">
      <textarea id="chat-input" placeholder="اكتب رسالتك لياسمين…" rows="1" onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
      <button id="send-btn" onclick="sendMsg()" title="إرسال">
        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
      </button>
    </div>
  </div>
</div>

<?php tool_ad(); ?>

<!-- SEO content block -->
<div style="max-width:800px;margin:24px auto;padding:0 16px">
  <div style="background:#fff;border-radius:16px;padding:28px 24px;border:1px solid #e8e0d8">
    <h2 style="font-size:22px;font-weight:800;color:#1e293b;margin:0 0 16px">ياسمين — أذكى مساعدة ذكاء اصطناعي عربية سورية مجانية</h2>
    <p style="font-size:14px;line-height:1.9;color:#475569;margin:0 0 14px">
      ياسمين هي مساعدة ذكاء اصطناعي متطورة تتحدث العربية بطلاقة وبلهجة سورية ساحلية دافئة. تم تطويرها بواسطة فريق yassota لتقديم تجربة محادثة ذكية ومجانية بالكامل للمستخدمين العرب. سواء كنت تبحث عن إجابات لأسئلة علمية معقدة، أو تريد وصفات طبخ شامية أصيلة، أو تحتاج مساعدة في البرمجة والتقنية، أو ببساطة تريد محادثة ممتعة — ياسمين موجودة لمساعدتك على مدار الساعة.
    </p>
    <h3 style="font-size:17px;font-weight:700;color:#1e293b;margin:0 0 10px">ما الذي يميز ياسمين عن غيرها؟</h3>
    <ul style="font-size:14px;line-height:1.9;color:#475569;padding-right:20px;margin:0 0 14px">
      <li><strong>لهجة سورية طبيعية:</strong> ترد بلهجة ساحلية (لاذقانية) محببة وطبيعية تماماً</li>
      <li><strong>ذاكرة محادثات:</strong> تحفظ محادثاتك السابقة ويمكنك العودة إليها في أي وقت</li>
      <li><strong>مجانية 100%:</strong> بدون تسجيل، بدون اشتراك، بدون حدود</li>
      <li><strong>متعددة المجالات:</strong> علوم، تقنية، طبخ، تاريخ، أدب، برمجة، صحة، تعليم</li>
      <li><strong>سريعة وذكية:</strong> تستخدم أحدث نماذج الذكاء الاصطناعي العالمية</li>
      <li><strong>خصوصية تامة:</strong> محادثاتك خاصة ومحمية بالكامل</li>
    </ul>
    <h3 style="font-size:17px;font-weight:700;color:#1e293b;margin:0 0 10px">مجالات يمكن لياسمين مساعدتك فيها</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;margin-bottom:14px">
      <?php
      $areas = [
        ['🔬','العلوم والفيزياء'],['📚','التاريخ والثقافة'],['🍳','الطبخ الشامي'],['💻','البرمجة والتقنية'],
        ['📝','الكتابة والأدب'],['🏥','الصحة والطب'],['📊','الرياضيات'],['🎓','التعليم والدراسة'],
        ['🌍','الجغرافيا'],['⚖️','القانون'],['💼','الأعمال والتسويق'],['🎨','التصميم والإبداع'],
      ];
      foreach ($areas as $a): ?>
      <div style="padding:8px 12px;background:#faf8f5;border-radius:10px;border:1px solid #e8e0d8;font-size:13px;color:#334155">
        <span style="margin-left:4px"><?= $a[0] ?></span> <?= $a[1] ?>
      </div>
      <?php endforeach; ?>
    </div>
    <h3 style="font-size:17px;font-weight:700;color:#1e293b;margin:0 0 10px">أسئلة شائعة عن ياسمين</h3>
    <div style="font-size:14px;color:#475569;line-height:1.9">
      <p style="margin:0 0 8px"><strong>هل ياسمين مجانية فعلاً؟</strong><br>نعم، ياسمين مجانية بالكامل ولا تحتاج أي تسجيل أو اشتراك. استخدمها بقدر ما تشاء.</p>
      <p style="margin:0 0 8px"><strong>هل تحفظ ياسمين محادثاتي؟</strong><br>نعم، ياسمين تحفظ محادثاتك السابقة على جهازك ويمكنك العودة إليها في أي وقت من القائمة الجانبية.</p>
      <p style="margin:0 0 8px"><strong>هل يمكنني استخدام ياسمين للبرمجة؟</strong><br>بالتأكيد! ياسمين تساعدك في كتابة الأكواد البرمجية، تصحيح الأخطاء، شرح المفاهيم، والإجابة على أسئلة تقنية بكل اللغات البرمجية.</p>
      <p style="margin:0"><strong>ما اللغات التي تتحدثها ياسمين؟</strong><br>ياسمين تتحدث العربية (فصحى ولهجة سورية) بشكل رئيسي، لكنها تفهم وتتحدث الإنجليزية والفرنسية ولغات أخرى أيضاً.</p>
    </div>
  </div>
</div>
</main>

<script>
const msgs = document.getElementById('chat-messages');
const input = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
const convList = document.getElementById('conv-list');
let history = [];
let streaming = false;
let currentConvId = 0;

// Session cookie
if (!getCookie('yasmin_sid')) {
  const sid = Array.from(crypto.getRandomValues(new Uint8Array(16))).map(b=>b.toString(16).padStart(2,'0')).join('');
  document.cookie = 'yasmin_sid=' + sid + ';path=/;max-age=31536000;SameSite=Lax';
}

function getCookie(n) {
  const m = document.cookie.match(new RegExp('(?:^|; )' + n + '=([^;]*)'));
  return m ? decodeURIComponent(m[1]) : '';
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function now() {
  return new Date().toLocaleTimeString('ar-SA', {hour:'2-digit', minute:'2-digit'});
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('show');
}

function addMsg(role, text, typing = false) {
  const welcome = document.getElementById('welcome');
  if (welcome) welcome.style.display = 'none';

  const row = document.createElement('div');
  row.className = 'msg-row ' + (role === 'user' ? 'user' : 'ai');

  const avatar = document.createElement('div');
  avatar.className = 'msg-avatar ' + (role === 'user' ? 'user' : 'ai');
  avatar.textContent = role === 'user' ? '👤' : '🌸';

  const wrap = document.createElement('div');
  const bubble = document.createElement('div');
  bubble.className = 'bubble ' + (role === 'user' ? 'user' : 'ai');

  if (typing) {
    bubble.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
  } else {
    bubble.textContent = text;
  }

  const time = document.createElement('div');
  time.className = 'bubble-time';
  time.textContent = now();

  wrap.appendChild(bubble);
  wrap.appendChild(time);
  row.appendChild(avatar);
  row.appendChild(wrap);
  msgs.appendChild(row);
  msgs.scrollTop = msgs.scrollHeight;
  return bubble;
}

function send(text) {
  input.value = text;
  sendMsg();
}

async function sendMsg() {
  const text = input.value.trim();
  if (!text || streaming) return;

  addMsg('user', text);
  history.push({role: 'user', content: text});
  input.value = '';
  input.style.height = 'auto';
  sendBtn.disabled = true;
  streaming = true;

  const typingBubble = addMsg('ai', '', true);

  try {
    const resp = await fetch('?action=chat', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        message: text,
        history: history.slice(0, -1),
        conversation_id: currentConvId
      }),
    });

    if (!resp.ok || !resp.body) throw new Error('Network error');

    const reader = resp.body.getReader();
    const decoder = new TextDecoder();
    let aiText = '';
    let sseBuffer = '';
    let currentEvent = 'message';

    typingBubble.innerHTML = '';

    while (true) {
      const {done, value} = await reader.read();
      if (done) break;
      sseBuffer += decoder.decode(value, {stream: true});

      const lines = sseBuffer.split('\n');
      sseBuffer = lines.pop(); // keep incomplete line

      for (const line of lines) {
        const trimmed = line.trim();
        if (!trimmed) { currentEvent = 'message'; continue; } // empty line resets event
        if (trimmed.startsWith(':')) continue; // SSE comment

        if (trimmed.startsWith('event:')) {
          currentEvent = trimmed.slice(6).trim();
          continue;
        }

        if (trimmed.startsWith('data:')) {
          const data = trimmed.slice(5).trim();

          if (currentEvent === 'chunk') {
            try {
              const parsed = JSON.parse(data);
              if (parsed.t !== undefined) {
                aiText += parsed.t;
                typingBubble.textContent = aiText;
                msgs.scrollTop = msgs.scrollHeight;
              }
            } catch(e) {}
          } else if (currentEvent === 'error') {
            try {
              const parsed = JSON.parse(data);
              const errMsg = parsed.msg || parsed.error || 'حدث خطأ';
              typingBubble.textContent = '⚠️ ' + errMsg;
              typingBubble.className = 'bubble error';
            } catch(e) {
              typingBubble.textContent = '⚠️ حدث خطأ غير متوقع';
              typingBubble.className = 'bubble error';
            }
          } else if (currentEvent === 'conv') {
            try {
              const parsed = JSON.parse(data);
              if (typeof parsed === 'string') {
                const inner = JSON.parse(parsed);
                if (inner.id) currentConvId = inner.id;
              } else if (parsed.id) {
                currentConvId = parsed.id;
              }
            } catch(e) {}
          } else if (currentEvent === 'done') {
            // stream finished
          }
        }
      }
    }

    if (aiText) {
      history.push({role: 'assistant', content: aiText});
    }
    // Refresh sidebar
    loadConversations();

  } catch (e) {
    typingBubble.textContent = '⚠️ حدث خطأ في الاتصال — جرّب مرة أخرى';
    typingBubble.className = 'bubble error';
  }

  sendBtn.disabled = false;
  streaming = false;
  input.focus();
  msgs.scrollTop = msgs.scrollHeight;
}

function handleKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMsg();
  }
}

// ── Conversation management ────────────────────
async function loadConversations() {
  try {
    const resp = await fetch('?action=conversations');
    const data = await resp.json();
    convList.innerHTML = '';
    if (!data.length) {
      convList.innerHTML = '<div style="padding:16px;text-align:center;color:rgba(255,255,255,.3);font-size:12px">لا توجد محادثات سابقة</div>';
      return;
    }
    for (const c of data) {
      const item = document.createElement('div');
      item.className = 'ys-conv-item' + (c.id == currentConvId ? ' active' : '');
      item.innerHTML = `<span class="title">${escHtml(c.title)}</span>
        <span class="meta">${c.message_count}</span>
        <button class="ys-conv-delete" onclick="event.stopPropagation();deleteConv(${c.id})" title="حذف">🗑</button>`;
      item.onclick = () => loadConv(c.id);
      convList.appendChild(item);
    }
  } catch(e) {}
}

async function loadConv(id) {
  try {
    const resp = await fetch('?action=load&id=' + id);
    const data = await resp.json();
    if (data.error) return;

    currentConvId = id;
    history = [];
    msgs.innerHTML = '';

    for (const m of data.messages) {
      addMsg(m.role, m.content);
      history.push({role: m.role, content: m.content});
    }
    loadConversations();
    // Close sidebar on mobile
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').classList.remove('show');
    msgs.scrollTop = msgs.scrollHeight;
  } catch(e) {}
}

async function deleteConv(id) {
  if (!confirm('حذف هذه المحادثة؟')) return;
  await fetch('?action=delete_conv&id=' + id);
  if (currentConvId === id) newChat();
  loadConversations();
}

function newChat() {
  currentConvId = 0;
  history = [];
  msgs.innerHTML = `<div class="welcome-banner" id="welcome">
    <span class="icon">🌸</span>
    <div>
      <h3>أهلاً وسهلاً! أنا ياسمين 🇸🇾</h3>
      <p>مساعدتك الذكية السورية — اسألني أي شي بدك ياه: علوم، تاريخ، طبخ شامي، تقنية، برمجة، صحة، أو أي موضوع تاني!</p>
      <div class="quick-chips">
        <button class="quick-chip" onclick="send('أخبريني عن دمشق وتاريخها')">🏛️ عن دمشق</button>
        <button class="quick-chip" onclick="send('وصفة كبة حلبية أصلية')">🍽️ كبة حلبية</button>
        <button class="quick-chip" onclick="send('اشرح لي الذكاء الاصطناعي ببساطة')">🤖 الذكاء الاصطناعي</button>
        <button class="quick-chip" onclick="send('اكتب لي قصيدة عن سوريا')">📝 قصيدة سورية</button>
        <button class="quick-chip" onclick="send('ساعدني بكتابة كود برمجي')">💻 برمجة</button>
      </div>
    </div>
  </div>`;
  loadConversations();
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('show');
}

function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

// Init
loadConversations();
input.focus();
</script>
<?php tool_footer(); ?>
