<?php
/* Social API router — handles /api/* endpoints */
ob_start();
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

$me = sc_user($pdo);

// Route: /api/{action}
$action = trim($_GET['action'] ?? '', '/');

/* ── Helpers ── */
function require_auth(?array $me): void {
    if (!$me) sc_json(['ok'=>false,'error'=>'يجب تسجيل الدخول أولاً'], 401);
}
function json_body(): array {
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

/* ══ POST: create ══ */
if ($action === 'post/create') {
    require_auth($me);
    $body    = json_body();
    $content = trim($body['content'] ?? '');
    $type    = in_array($body['post_type'] ?? '', ['post','script','article']) ? $body['post_type'] : 'post';
    if (!$content) sc_json(['ok'=>false,'error'=>'المحتوى مطلوب']);
    if (!sc_is_clean($content)) sc_json(['ok'=>false,'error'=>'المحتوى يخالف معايير المجتمع']);
    $pdo->prepare("INSERT INTO posts (user_id,content,post_type,visibility) VALUES (?,?,?,'public')")
        ->execute([$me['id'], $content, $type]);
    $pdo->prepare("UPDATE users SET post_count=post_count+1 WHERE id=?")->execute([$me['id']]);
    sc_json(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
}

/* ══ POST: like ══ */
if ($action === 'like') {
    require_auth($me);
    $body   = json_body();
    $postId = (int)($body['post_id'] ?? 0);
    if (!$postId) sc_json(['ok'=>false,'error'=>'post_id مطلوب']);
    $existing = $pdo->prepare("SELECT 1 FROM likes WHERE user_id=? AND post_id=?");
    $existing->execute([$me['id'], $postId]);
    if ($existing->fetchColumn()) {
        $pdo->prepare("DELETE FROM likes WHERE user_id=? AND post_id=?")->execute([$me['id'], $postId]);
        $pdo->prepare("UPDATE posts SET like_count=GREATEST(like_count-1,0) WHERE id=?")->execute([$postId]);
        $liked = false;
    } else {
        $pdo->prepare("INSERT IGNORE INTO likes (user_id,post_id) VALUES (?,?)")->execute([$me['id'], $postId]);
        $pdo->prepare("UPDATE posts SET like_count=like_count+1 WHERE id=?")->execute([$postId]);
        $liked = true;
    }
    $count = (int)$pdo->query("SELECT like_count FROM posts WHERE id={$postId}")->fetchColumn();
    sc_json(['ok'=>true,'liked'=>$liked,'likes'=>$count]);
}

/* ══ POST: follow ══ */
if ($action === 'follow') {
    require_auth($me);
    $body   = json_body();
    $userId = (int)($body['user_id'] ?? 0);
    if (!$userId || $userId === $me['id']) sc_json(['ok'=>false,'error'=>'معرّف المستخدم غير صالح']);
    $existing = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id=? AND following_id=?");
    $existing->execute([$me['id'], $userId]);
    if ($existing->fetchColumn()) {
        $pdo->prepare("DELETE FROM follows WHERE follower_id=? AND following_id=?")->execute([$me['id'], $userId]);
        $pdo->prepare("UPDATE users SET follower_count=GREATEST(follower_count-1,0) WHERE id=?")->execute([$userId]);
        $pdo->prepare("UPDATE users SET following_count=GREATEST(following_count-1,0) WHERE id=?")->execute([$me['id']]);
        sc_json(['ok'=>true,'following'=>false]);
    } else {
        $pdo->prepare("INSERT IGNORE INTO follows (follower_id,following_id) VALUES (?,?)")->execute([$me['id'], $userId]);
        $pdo->prepare("UPDATE users SET follower_count=follower_count+1 WHERE id=?")->execute([$userId]);
        $pdo->prepare("UPDATE users SET following_count=following_count+1 WHERE id=?")->execute([$me['id']]);
        // Notify
        $pdo->prepare("INSERT INTO notifications (user_id,from_id,type,body) VALUES (?,?,?,?)")
            ->execute([$userId, $me['id'], 'follow', sc_h($me['display_name'] ?: $me['username']) . ' بدأ بمتابعتك']);
        sc_json(['ok'=>true,'following'=>true]);
    }
}

/* ══ GET: notifications ══ */
if ($action === 'notifications') {
    require_auth($me);
    $st = $pdo->prepare("SELECT n.*, u.username, u.avatar FROM notifications n
        LEFT JOIN users u ON u.id=n.from_id
        WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 30");
    $st->execute([$me['id']]);
    sc_json(['ok'=>true,'items'=>$st->fetchAll()]);
}

/* ══ POST: comment ══ */
if ($action === 'comment') {
    require_auth($me);
    $body    = json_body();
    $postId  = (int)($body['post_id'] ?? 0);
    $content = trim($body['content'] ?? '');
    if (!$postId || !$content) sc_json(['ok'=>false,'error'=>'بيانات ناقصة']);
    if (!sc_is_clean($content)) sc_json(['ok'=>false,'error'=>'المحتوى يخالف معايير المجتمع']);
    $pdo->prepare("INSERT INTO comments (post_id,user_id,content) VALUES (?,?,?)")->execute([$postId,$me['id'],$content]);
    $pdo->prepare("UPDATE posts SET comment_count=comment_count+1 WHERE id=?")->execute([$postId]);
    sc_json(['ok'=>true]);
}

/* ══ GET: messages/list ══ */
if ($action === 'messages/list') {
    require_auth($me);
    $st = $pdo->prepare("
        SELECT c.*, cm.last_read,
          (SELECT COUNT(*) FROM messages m WHERE m.conv_id=c.id AND m.created_at > COALESCE(cm.last_read,'1970-01-01')) as unread
        FROM conversations c
        JOIN conversation_members cm ON cm.conv_id=c.id AND cm.user_id=?
        ORDER BY c.last_msg_at DESC LIMIT 50
    ");
    $st->execute([$me['id']]);
    sc_json(['ok'=>true,'items'=>$st->fetchAll()]);
}

/* ══ GET: messages/get ══ */
if ($action === 'messages/get') {
    require_auth($me);
    $convId = (int)($_GET['conv_id'] ?? 0);
    $check = $pdo->prepare("SELECT 1 FROM conversation_members WHERE conv_id=? AND user_id=?");
    $check->execute([$convId, $me['id']]);
    if (!$check->fetchColumn()) sc_json(['ok'=>false,'error'=>'غير مصرح'], 403);
    $st = $pdo->prepare("
        SELECT m.*, u.username, u.display_name, u.avatar, u.verified
        FROM messages m JOIN users u ON u.id=m.user_id
        WHERE m.conv_id=? AND m.is_deleted=0
        ORDER BY m.created_at ASC LIMIT 100
    ");
    $st->execute([$convId]);
    $pdo->prepare("UPDATE conversation_members SET last_read=NOW() WHERE conv_id=? AND user_id=?")->execute([$convId,$me['id']]);
    sc_json(['ok'=>true,'items'=>$st->fetchAll()]);
}

/* ══ POST: messages/send ══ */
if ($action === 'messages/send') {
    require_auth($me);
    $body    = json_body();
    $convId  = (int)($body['conv_id']  ?? 0);
    $toUser  = (int)($body['to_user']  ?? 0);
    $content = trim($body['content']   ?? '');
    if (!$content) sc_json(['ok'=>false,'error'=>'الرسالة فارغة']);
    if (!sc_is_clean($content)) sc_json(['ok'=>false,'error'=>'المحتوى يخالف معايير المجتمع']);

    // Create direct conversation if needed
    if (!$convId && $toUser && $toUser !== $me['id']) {
        // Find existing direct conv
        $st = $pdo->prepare("
            SELECT c.id FROM conversations c
            JOIN conversation_members m1 ON m1.conv_id=c.id AND m1.user_id=?
            JOIN conversation_members m2 ON m2.conv_id=c.id AND m2.user_id=?
            WHERE c.type='direct' LIMIT 1
        ");
        $st->execute([$me['id'], $toUser]);
        $convId = (int)$st->fetchColumn();
        if (!$convId) {
            $pdo->prepare("INSERT INTO conversations (type,created_by) VALUES ('direct',?)")->execute([$me['id']]);
            $convId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO conversation_members (conv_id,user_id) VALUES (?,?),(?,?)")
                ->execute([$convId,$me['id'],$convId,$toUser]);
        }
    }

    if (!$convId) sc_json(['ok'=>false,'error'=>'المحادثة غير موجودة']);
    $check = $pdo->prepare("SELECT 1 FROM conversation_members WHERE conv_id=? AND user_id=?");
    $check->execute([$convId, $me['id']]);
    if (!$check->fetchColumn()) sc_json(['ok'=>false,'error'=>'غير مصرح'], 403);

    $pdo->prepare("INSERT INTO messages (conv_id,user_id,content) VALUES (?,?,?)")->execute([$convId,$me['id'],$content]);
    $pdo->prepare("UPDATE conversations SET last_msg_at=NOW() WHERE id=?")->execute([$convId]);
    sc_json(['ok'=>true,'conv_id'=>$convId,'msg_id'=>(int)$pdo->lastInsertId()]);
}

/* ══ POST: report ══ */
if ($action === 'report') {
    require_auth($me);
    $body   = json_body();
    $type   = in_array($body['ref_type'] ?? '', ['post','comment','user','tool']) ? $body['ref_type'] : null;
    $refId  = (int)($body['ref_id'] ?? 0);
    $reason = trim($body['reason'] ?? '');
    if (!$type || !$refId || !$reason) sc_json(['ok'=>false,'error'=>'بيانات ناقصة']);
    $pdo->prepare("INSERT INTO reports (reporter_id,ref_type,ref_id,reason) VALUES (?,?,?,?)")
        ->execute([$me['id'],$type,$refId,$reason]);
    sc_json(['ok'=>true]);
}

sc_json(['ok'=>false,'error'=>'endpoint غير موجود'], 404);
