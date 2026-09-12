<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
$me = current_user();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

function jout($d) { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function need_auth() { global $me; if (!$me) jout(['ok' => false, 'error' => 'login', 'need_login' => true]); return $me; }
function need_csrf() { if (!csrf_ok()) jout(['ok' => false, 'error' => 'csrf']); }
function notify(int $userId, ?int $actorId, string $type, ?int $postId = null, ?int $commentId = null): void {
    global $pdo; if ($userId === $actorId) return;
    $pdo->prepare("INSERT INTO notifications (user_id,actor_id,type,post_id,comment_id) VALUES (?,?,?,?,?)")
        ->execute([$userId, $actorId, $type, $postId, $commentId]);
}

switch ($action) {

case 'feed': {
    $mode = $_POST['mode'] ?? 'explore'; $arg = $_POST['arg'] ?? ''; $page = max(1, (int)($_POST['page'] ?? 1));
    $layout = ($_POST['layout'] ?? 'cards') === 'tiles' ? 'tiles' : 'cards';
    $rows = fetch_feed($mode, $arg, $page);
    jout(['ok' => true, 'html' => $layout === 'tiles' ? render_tiles($rows) : render_cards($rows), 'count' => count($rows), 'per' => YA_PER]);
}

case 'like': {
    need_csrf(); $me = need_auth(); $pid = (int)($_POST['id'] ?? 0);
    $ex = $pdo->prepare("SELECT 1 FROM post_likes WHERE user_id=? AND post_id=?"); $ex->execute([$me['id'], $pid]);
    if ($ex->fetch()) {
        $pdo->prepare("DELETE FROM post_likes WHERE user_id=? AND post_id=?")->execute([$me['id'], $pid]);
        $pdo->prepare("UPDATE posts SET likes_count=GREATEST(0,likes_count-1) WHERE id=?")->execute([$pid]);
        $liked = false;
    } else {
        $pdo->prepare("INSERT IGNORE INTO post_likes (user_id,post_id) VALUES (?,?)")->execute([$me['id'], $pid]);
        $pdo->prepare("UPDATE posts SET likes_count=likes_count+1 WHERE id=?")->execute([$pid]);
        $liked = true;
        $o = $pdo->prepare("SELECT user_id FROM posts WHERE id=?"); $o->execute([$pid]);
        if ($r = $o->fetch()) notify((int)$r['user_id'], (int)$me['id'], 'like', $pid);
    }
    $c = $pdo->prepare("SELECT likes_count FROM posts WHERE id=?"); $c->execute([$pid]);
    jout(['ok' => true, 'liked' => $liked, 'count' => (int)($c->fetch()['likes_count'] ?? 0)]);
}

case 'save': {
    need_csrf(); $me = need_auth(); $pid = (int)($_POST['id'] ?? 0);
    $ex = $pdo->prepare("SELECT 1 FROM post_saves WHERE user_id=? AND post_id=?"); $ex->execute([$me['id'], $pid]);
    if ($ex->fetch()) { $pdo->prepare("DELETE FROM post_saves WHERE user_id=? AND post_id=?")->execute([$me['id'], $pid]); $pdo->prepare("UPDATE posts SET saves_count=GREATEST(0,saves_count-1) WHERE id=?")->execute([$pid]); $saved = false; }
    else { $pdo->prepare("INSERT IGNORE INTO post_saves (user_id,post_id) VALUES (?,?)")->execute([$me['id'], $pid]); $pdo->prepare("UPDATE posts SET saves_count=saves_count+1 WHERE id=?")->execute([$pid]); $saved = true; }
    jout(['ok' => true, 'saved' => $saved]);
}

case 'follow': {
    need_csrf(); $me = need_auth(); $tid = (int)($_POST['id'] ?? 0);
    if ($tid === (int)$me['id']) jout(['ok' => false, 'error' => 'self']);
    $ex = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id=? AND following_id=?"); $ex->execute([$me['id'], $tid]);
    if ($ex->fetch()) {
        $pdo->prepare("DELETE FROM follows WHERE follower_id=? AND following_id=?")->execute([$me['id'], $tid]);
        $pdo->prepare("UPDATE users SET followers_count=GREATEST(0,followers_count-1) WHERE id=?")->execute([$tid]);
        $pdo->prepare("UPDATE users SET following_count=GREATEST(0,following_count-1) WHERE id=?")->execute([$me['id']]);
        $following = false;
    } else {
        $pdo->prepare("INSERT IGNORE INTO follows (follower_id,following_id) VALUES (?,?)")->execute([$me['id'], $tid]);
        $pdo->prepare("UPDATE users SET followers_count=followers_count+1 WHERE id=?")->execute([$tid]);
        $pdo->prepare("UPDATE users SET following_count=following_count+1 WHERE id=?")->execute([$me['id']]);
        $following = true; notify($tid, (int)$me['id'], 'follow');
    }
    jout(['ok' => true, 'following' => $following]);
}

case 'share': {
    $pid = (int)($_POST['id'] ?? 0);
    if ($pid) $pdo->prepare("UPDATE posts SET shares_count=shares_count+1 WHERE id=?")->execute([$pid]);
    jout(['ok' => true]);
}

case 'comment': {
    need_csrf(); $me = need_auth();
    if (!rate_ok('cmt:' . $me['id'], 30, 600)) jout(['ok' => false, 'error' => 'محاولات كثيرة، انتظر قليلاً.']);
    $pid = (int)($_POST['post_id'] ?? 0); $parent = (int)($_POST['parent_id'] ?? 0) ?: null;
    $body = trim($_POST['body'] ?? '');
    if ($body === '' || mb_strlen($body) > 1000) jout(['ok' => false, 'error' => 'تعليق غير صالح.']);
    $po = $pdo->prepare("SELECT user_id FROM posts WHERE id=? AND status='published'"); $po->execute([$pid]);
    $post = $po->fetch(); if (!$post) jout(['ok' => false, 'error' => 'المنشور غير موجود.']);
    $pdo->prepare("INSERT INTO comments (post_id,user_id,parent_id,body) VALUES (?,?,?,?)")->execute([$pid, $me['id'], $parent, $body]);
    $cid = (int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE posts SET comments_count=comments_count+1 WHERE id=?")->execute([$pid]);
    notify((int)$post['user_id'], (int)$me['id'], 'comment', $pid, $cid);
    if ($parent) { $pc = $pdo->prepare("SELECT user_id FROM comments WHERE id=?"); $pc->execute([$parent]); if ($r = $pc->fetch()) notify((int)$r['user_id'], (int)$me['id'], 'reply', $pid, $cid); }
    $g = $pdo->prepare("SELECT c.*, u.username author_username, u.name author_name, u.avatar author_avatar, u.verified author_verified FROM comments c JOIN users u ON u.id=c.user_id WHERE c.id=?");
    $g->execute([$cid]); $row = $g->fetch(); $row['replies'] = [];
    ob_start(); render_comment($row, (bool)$parent); $html = ob_get_clean();
    $tc = $pdo->prepare("SELECT comments_count FROM posts WHERE id=?"); $tc->execute([$pid]);
    jout(['ok' => true, 'html' => $html, 'total' => num_fmt($tc->fetch()['comments_count'])]);
}

case 'comment_delete': {
    need_csrf(); $me = need_auth(); $cid = (int)($_POST['id'] ?? 0);
    $c = $pdo->prepare("SELECT * FROM comments WHERE id=?"); $c->execute([$cid]); $row = $c->fetch();
    if (!$row) jout(['ok' => false, 'error' => 'غير موجود']);
    if ((int)$row['user_id'] !== (int)$me['id'] && (int)$me['is_admin'] !== 1) jout(['ok' => false, 'error' => 'غير مصرّح']);
    $n = $pdo->prepare("SELECT COUNT(*) c FROM comments WHERE id=? OR parent_id=?"); $n->execute([$cid, $cid]); $del = (int)$n->fetch()['c'];
    $pdo->prepare("DELETE FROM comments WHERE id=? OR parent_id=?")->execute([$cid, $cid]);
    $pdo->prepare("UPDATE posts SET comments_count=GREATEST(0,comments_count-?) WHERE id=?")->execute([$del, $row['post_id']]);
    jout(['ok' => true]);
}

case 'report': {
    need_csrf(); $me = need_auth();
    $type = in_array($_POST['target_type'] ?? '', ['post', 'comment', 'user'], true) ? $_POST['target_type'] : '';
    $tid = (int)($_POST['target_id'] ?? 0);
    $reason = mb_substr($_POST['reason'] ?? 'other', 0, 30);
    if (!$type || !$tid) jout(['ok' => false, 'error' => 'بيانات ناقصة']);
    if (!rate_ok('rep:' . $me['id'], 20, 3600)) jout(['ok' => false, 'error' => 'محاولات كثيرة']);
    $pdo->prepare("INSERT INTO reports (reporter_id,target_type,target_id,reason) VALUES (?,?,?,?)")->execute([$me['id'], $type, $tid, $reason]);
    jout(['ok' => true]);
}

case 'suggest': {
    $q = trim($_POST['q'] ?? ''); if (mb_strlen($q) < 2) jout(['ok' => false]);
    $like = '%' . $q . '%'; $html = '';
    $us = $pdo->prepare("SELECT username,name,avatar,verified,followers_count FROM users WHERE banned=0 AND (username LIKE ? OR name LIKE ?) LIMIT 4");
    $us->execute([$like, $like]);
    foreach ($us->fetchAll() as $u) $html .= '<a href="' . e(user_url($u['username'])) . '">' . avatar_html($u, 30) . '<span style="min-width:0"><b style="display:block;font-size:13.5px">' . e($u['name'] ?: $u['username']) . '</b><small style="color:var(--faint);font-size:12px">@' . e($u['username']) . '</small></span></a>';
    $ts = $pdo->prepare("SELECT slug,name,posts_count FROM hashtags WHERE name LIKE ? ORDER BY posts_count DESC LIMIT 3");
    $ts->execute([$like]);
    foreach ($ts->fetchAll() as $t) $html .= '<a href="' . e(tag_url($t['slug'])) . '"><span style="width:30px;text-align:center;color:var(--brand)">#</span><span><b style="font-size:13.5px">#' . e($t['name']) . '</b> <small style="color:var(--faint)">· ' . num_fmt($t['posts_count']) . '</small></span></a>';
    $ps = $pdo->prepare("SELECT slug,title FROM posts WHERE status='published' AND visibility='public' AND title LIKE ? ORDER BY created_at DESC LIMIT 3");
    $ps->execute([$like]);
    foreach ($ps->fetchAll() as $p) $html .= '<a href="' . e(url('post/' . $p['slug'])) . '">' . icon('news', 20) . '<span style="min-width:0"><b style="font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">' . e($p['title']) . '</b></span></a>';
    if ($html === '') $html = '<div style="padding:12px;color:var(--faint);font-size:13px">لا نتائج</div>';
    $html .= '<a href="' . e(url('search?q=' . urlencode($q))) . '" style="justify-content:center;color:var(--brand-ink);font-weight:600">عرض كل نتائج «' . e($q) . '»</a>';
    jout(['ok' => true, 'html' => $html]);
}

case 'notif_count': {
    if (!$me) jout(['ok' => true, 'count' => 0]);
    $c = $pdo->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0"); $c->execute([$me['id']]);
    jout(['ok' => true, 'count' => (int)$c->fetch()['c']]);
}

case 'msg_send': {
    need_csrf(); $me = need_auth();
    $to = (int)($_POST['to'] ?? 0); $body = trim($_POST['body'] ?? '');
    if (!$to || $to === (int)$me['id'] || $body === '') jout(['ok' => false, 'error' => 'بيانات ناقصة']);
    if (!rate_ok('msg:' . $me['id'], 60, 300)) jout(['ok' => false, 'error' => 'محاولات كثيرة']);
    $a = min($me['id'], $to); $b = max($me['id'], $to);
    $pdo->prepare("INSERT INTO conversations (user_a,user_b) VALUES (?,?) ON DUPLICATE KEY UPDATE last_at=NOW()")->execute([$a, $b]);
    $cv = $pdo->prepare("SELECT id FROM conversations WHERE user_a=? AND user_b=?"); $cv->execute([$a, $b]); $cid = (int)$cv->fetch()['id'];
    $pdo->prepare("INSERT INTO messages (conversation_id,sender_id,body) VALUES (?,?,?)")->execute([$cid, $me['id'], mb_substr($body, 0, 2000)]);
    // التقط المعرّف قبل notify()، وإلا أعاد lastInsertId معرّف الإشعار لا الرسالة
    $mid = (int)$pdo->lastInsertId();
    notify($to, (int)$me['id'], 'message');
    jout(['ok' => true, 'id' => $mid]);
}

case 'msg_list': {
    $me = need_auth();
    $to = (int)($_POST['to'] ?? 0); $after = (int)($_POST['after'] ?? 0);
    $a = min($me['id'], $to); $b = max($me['id'], $to);
    $cv = $pdo->prepare("SELECT id FROM conversations WHERE user_a=? AND user_b=?"); $cv->execute([$a, $b]);
    $conv = $cv->fetch(); if (!$conv) jout(['ok' => true, 'messages' => []]);
    $cid = (int)$conv['id'];
    $pdo->prepare("UPDATE messages SET seen=1 WHERE conversation_id=? AND sender_id<>? AND seen=0")->execute([$cid, $me['id']]);
    $st = $pdo->prepare("SELECT id,sender_id,body,seen,created_at FROM messages WHERE conversation_id=? AND id>? ORDER BY id ASC LIMIT 100");
    $st->execute([$cid, $after]);
    $msgs = array_map(fn($m) => ['id' => (int)$m['id'], 'mine' => (int)$m['sender_id'] === (int)$me['id'], 'body' => $m['body'], 'seen' => (int)$m['seen'], 'time' => time_ago($m['created_at'])], $st->fetchAll());
    jout(['ok' => true, 'messages' => $msgs]);
}

default:
    http_response_code(400);
    jout(['ok' => false, 'error' => 'إجراء غير معروف']);
}
