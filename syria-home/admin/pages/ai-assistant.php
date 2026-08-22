<?php
/**
 * Scoped AI assistant: Gemini/OpenRouter can only take actions from an
 * explicit whitelist, applied through the exact same validated write
 * paths as the manual admin forms. It can NEVER write files, run raw
 * SQL, or touch settings outside SETTINGS_WHITELIST below — that keeps
 * a prompt-injected or hallucinated response from ever turning into
 * code execution or credential tampering on a live site.
 */
const SETTINGS_WHITELIST = ['site_name', 'site_tagline', 'site_description', 'seo_default_keywords', 'social_twitter', 'social_facebook', 'social_linkedin'];

if (!isset($_SESSION['ai_chat'])) $_SESSION['ai_chat'] = [];

function ai_system_prompt(): string {
    return <<<SYS
You are the content-operations assistant inside a website admin panel. You may ONLY respond with a single minified JSON object (no markdown fences, no prose outside the JSON) using exactly one of these shapes:

1. Create a new article (always saved as a draft — you can never publish directly): {"action":"create_article","title":"...","content_type":"article|news|tutorial|comparison|review","excerpt":"...","body_html":"<h2>...</h2><p>...</p>","meta_title":"...","meta_description":"...","meta_keywords":"a,b,c","tags":"a,b,c"}
2. Edit an existing article (only include fields you're changing; you cannot change its publish status): {"action":"update_article","slug":"existing-slug","fields":{"title":"...","excerpt":"...","body_html":"...","meta_title":"...","meta_description":"...","meta_keywords":"...","tags":"...","trending":true}}
3. Edit an existing tool's copy (never invent a new tool_key): {"action":"update_tool","slug":"existing-slug","fields":{"short_description":"...","full_description":"...","meta_title":"...","meta_description":"...","meta_keywords":"..."}}
4. Update one site setting, ONLY key names from this exact whitelist: site_name, site_tagline, site_description, seo_default_keywords, social_twitter, social_facebook, social_linkedin — {"action":"update_setting","key":"site_tagline","value":"..."}
5. Just reply with information / ask a clarifying question, no changes made: {"action":"reply","message":"..."}

Never invent other action names or fields. If the user's request doesn't fit these 5 actions (e.g. asks you to edit PHP files, run SQL, change API keys/passwords, or anything outside content), respond with action "reply" explaining you're not able to do that from here and suggest the correct admin page.
SYS;
}

function ai_apply_action(PDO $pdo, array $j): string {
    $action = $j['action'] ?? '';

    if ($action === 'create_article') {
        $title = trim($j['title'] ?? '');
        if ($title === '') return 'Missing title — nothing created.';
        $slug = slugify($title);
        $body = $j['body_html'] ?? '';
        $fields = [
            'title' => $title, 'slug' => $slug,
            'content_type' => in_array($j['content_type'] ?? '', ['article','news','tutorial','comparison','review'], true) ? $j['content_type'] : 'article',
            'excerpt' => trim($j['excerpt'] ?? '') ?: excerpt_from_html($body),
            'body' => $body,
            'meta_title' => trim($j['meta_title'] ?? $title),
            'meta_description' => trim($j['meta_description'] ?? ''),
            'meta_keywords' => trim($j['meta_keywords'] ?? ''),
            'tags' => trim($j['tags'] ?? ''),
            'status' => 'draft', // AI-created content always lands as a draft — publishing is a deliberate human action in the Articles page.
            'reading_time' => reading_time_from_html($body),
            'published_at' => date('Y-m-d H:i:s'),
        ];
        try {
            $sql = "INSERT INTO articles (" . implode(',', array_keys($fields)) . ") VALUES (" . implode(',', array_map(fn($k) => ":$k", array_keys($fields))) . ")";
            $pdo->prepare($sql)->execute($fields);
        } catch (PDOException $e) {
            $fields['slug'] .= '-' . substr(md5((string)microtime(true)), 0, 5);
            $pdo->prepare("INSERT INTO articles (" . implode(',', array_keys($fields)) . ") VALUES (" . implode(',', array_map(fn($k) => ":$k", array_keys($fields))) . ")")->execute($fields);
        }
        log_ai_activity('create_article', 'article', (int)$pdo->lastInsertId(), 'Created "' . $title . '" (draft)');
        return 'Created article "' . $title . '" as a draft — review it in Articles, then publish it yourself when it looks right. Slug: ' . $fields['slug'];
    }

    if ($action === 'update_article') {
        $slug = trim($j['slug'] ?? '');
        // 'status' is intentionally excluded: the AI can draft/edit content but never flips an article live on its own.
        $allowed = ['title','excerpt','body_html','meta_title','meta_description','meta_keywords','tags','trending'];
        $stmt = $pdo->prepare("SELECT id FROM articles WHERE slug = ?"); $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) return "No article found with slug \"$slug\".";
        $set = []; $params = ['id' => $row['id']];
        foreach (($j['fields'] ?? []) as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $col = $k === 'body_html' ? 'body' : $k;
            $set[] = "$col = :$col";
            $params[$col] = $k === 'trending' ? (int)!!$v : $v;
        }
        if (!$set) return 'No recognized fields to update.';
        $set[] = 'updated_at = NOW()';
        $pdo->prepare("UPDATE articles SET " . implode(',', $set) . " WHERE id = :id")->execute($params);
        log_ai_activity('update_article', 'article', (int)$row['id'], 'Updated fields: ' . implode(', ', array_keys($params)));
        return 'Updated article "' . $slug . '".';
    }

    if ($action === 'update_tool') {
        $slug = trim($j['slug'] ?? '');
        $allowed = ['short_description','full_description','meta_title','meta_description','meta_keywords'];
        $stmt = $pdo->prepare("SELECT id FROM tools WHERE slug = ?"); $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) return "No tool found with slug \"$slug\".";
        $set = []; $params = ['id' => $row['id']];
        foreach (($j['fields'] ?? []) as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $set[] = "$k = :$k"; $params[$k] = $v;
        }
        if (!$set) return 'No recognized fields to update.';
        $pdo->prepare("UPDATE tools SET " . implode(',', $set) . " WHERE id = :id")->execute($params);
        log_ai_activity('update_tool', 'tool', (int)$row['id'], 'Updated copy for ' . $slug);
        return 'Updated tool "' . $slug . '".';
    }

    if ($action === 'update_setting') {
        $key = $j['key'] ?? '';
        if (!in_array($key, SETTINGS_WHITELIST, true)) return "\"$key\" isn't a setting I'm allowed to change from here. Use Settings for that.";
        set_setting($key, (string)($j['value'] ?? ''));
        log_ai_activity('update_setting', 'setting', null, "$key = " . $j['value']);
        return "Updated setting \"$key\".";
    }

    if ($action === 'reply') return $j['message'] ?? '...';

    return "I can only create/edit articles, edit tool descriptions, or update a small set of site settings from here.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'chat' && csrf_check()) {
    $userMsg = trim($_POST['message'] ?? '');
    if ($userMsg !== '') {
        $_SESSION['ai_chat'][] = ['role' => 'user', 'text' => $userMsg];
        $res = AIRouter::generate($userMsg, ai_system_prompt());
        if (!$res['ok']) {
            $_SESSION['ai_chat'][] = ['role' => 'assistant', 'text' => 'Error: ' . $res['error']];
        } else {
            $clean = trim(preg_replace('~^```json|```$~m', '', trim($res['text'])));
            $json = json_decode($clean, true);
            if (!$json || empty($json['action'])) {
                $_SESSION['ai_chat'][] = ['role' => 'assistant', 'text' => $res['text']];
            } else {
                $outcome = ai_apply_action($pdo, $json);
                $_SESSION['ai_chat'][] = ['role' => 'assistant', 'text' => $outcome];
            }
        }
    }
    header('Location: ?page=ai-assistant'); exit;
}

if (isset($_GET['clear'])) { $_SESSION['ai_chat'] = []; header('Location: ?page=ai-assistant'); exit; }
?>
<div class="card">
  <?php if (!GeminiClient::isConfigured() && !OpenRouterClient::isConfigured()): ?>
    <div class="flash err">No AI provider configured yet. Add a Gemini or OpenRouter API key in <a href="?page=settings&tab=api">Settings → API Keys</a>.</div>
  <?php endif; ?>

  <p class="hint">This assistant can draft/edit articles, tweak tool descriptions, and update a small set of site settings (name, tagline, description, socials). It always saves articles as drafts — publishing is a manual step you take in the Articles page — and it can never edit PHP files, run SQL, or change API keys/passwords.</p>

  <div style="max-height:440px;overflow:auto;border:1px solid var(--line);border-radius:12px;padding:16px;margin:16px 0;background:#fbfbfe">
    <?php if (!$_SESSION['ai_chat']): ?>
      <p class="hint" style="margin:0">Try: "Write a news article about the latest AI browser extensions and save it as a draft" or "Update the tagline to something punchier".</p>
    <?php endif; ?>
    <?php foreach ($_SESSION['ai_chat'] as $m): ?>
      <div style="margin-bottom:14px">
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--muted);margin-bottom:3px"><?= $m['role'] === 'user' ? 'You' : 'Assistant' ?></div>
        <div style="font-size:14px;white-space:pre-wrap"><?= e($m['text']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="chat">
    <div class="row2" style="grid-template-columns:1fr auto">
      <input type="text" name="message" placeholder="Tell the assistant what to do..." autofocus required>
      <button class="btn" type="submit"><i class="fa-solid fa-paper-plane"></i> Send</button>
    </div>
  </form>
  <a class="btn gray sm" style="margin-top:12px" href="?page=ai-assistant&clear=1">Clear conversation</a>
</div>
