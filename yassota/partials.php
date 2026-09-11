<?php
/* partials.php — تخطيط الصفحات المشترك، رأس SEO، وبطاقة المنشور */

function seo_head(array $o): void {
    $site = setting('site_name', 'YASSOTA');
    $title = $o['title'] ?? $site;
    $desc  = $o['description'] ?? setting('site_desc');
    $canon = $o['canonical'] ?? url(ltrim($_SERVER['REQUEST_URI'] ?? '', '/'));
    $canon = strtok($canon, '?');
    $ogimg = $o['image'] ?? (setting('og_image') ?: '');
    $type  = $o['og_type'] ?? 'website';
    $robots = ($o['noindex'] ?? false) ? 'noindex,nofollow' : 'index,follow,max-image-preview:large';
    $kw = $o['keywords'] ?? setting('site_keywords', '');
    $gv = setting('google_verification');
    $tw = setting('twitter_handle', '@yassota');
    ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if ($kw): ?><meta name="keywords" content="<?= e($kw) ?>"><?php endif; ?>
<meta name="robots" content="<?= e($robots) ?>">
<link rel="canonical" href="<?= e($canon) ?>">
<?php if ($gv): ?><meta name="google-site-verification" content="<?= e($gv) ?>"><?php endif; ?>
<meta property="og:site_name" content="<?= e($site) ?>">
<meta property="og:type" content="<?= e($type) ?>">
<meta property="og:title" content="<?= e($o['og_title'] ?? $title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canon) ?>">
<?php if (is_real_image($ogimg)): ?><meta property="og:image" content="<?= e($ogimg) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="<?= e($tw) ?>">
<meta name="twitter:title" content="<?= e($title) ?>">
<meta name="twitter:description" content="<?= e($desc) ?>">
<?php if (is_real_image($ogimg)): ?><meta name="twitter:image" content="<?= e($ogimg) ?>"><?php endif; ?>
<meta name="theme-color" content="#0b0d12" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<link rel="icon" href="<?= e(url('assets/favicon.svg')) ?>" type="image/svg+xml">
<link rel="manifest" href="<?= e(url('manifest.json')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>?v=<?= YASSOTA ?>">
<?php if (!empty($o['jsonld'])) foreach ((array)$o['jsonld'] as $ld): ?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endforeach;
}

function nav_items(): array {
    $me = current_user();
    if ($me) return [
        ['home', url(''), 'الرئيسية', 'home'],
        ['compass', url('explore'), 'استكشاف', 'explore'],
        ['plus-sq', url('create'), 'إنشاء', 'create'],
        ['bell', url('notifications'), 'الإشعارات', 'notifications'],
        ['user', user_url($me['username']), 'ملفي', 'profile'],
    ];
    return [
        ['home', url(''), 'الرئيسية', 'home'],
        ['compass', url('explore'), 'استكشاف', 'explore'],
        ['fire', url('trending'), 'الترند', 'trending'],
        ['user', url('login'), 'دخول', 'login'],
    ];
}

function avatar_html(array $u, int $size = 40): string {
    $st = "width:{$size}px;height:{$size}px";
    if (is_real_image($u['avatar'] ?? '')) {
        return '<img class="av" loading="lazy" src="' . e($u['avatar']) . '" alt="' . e($u['name'] ?? $u['username']) . '" style="' . $st . '">';
    }
    $ini = mb_strtoupper(mb_substr($u['name'] ?: $u['username'], 0, 1));
    return '<span class="av av-ph" style="' . $st . ';background:' . e(avatar_bg($u)) . ';font-size:' . round($size * 0.42) . 'px">' . e($ini) . '</span>';
}
function verified(array $u): string {
    return (int)($u['verified'] ?? 0) === 1 ? '<span class="vb" title="موثّق">' . icon('verified', 15) . '</span>' : '';
}

function layout_top(array $seo = []): void {
    $me = current_user();
    $active = $seo['active'] ?? '';
    $lang = setting('lang', 'ar'); $dir = $lang === 'en' ? 'ltr' : 'rtl';
    echo "<!doctype html><html lang=\"" . e($lang) . "\" dir=\"$dir\"><head>";
    echo '<script>try{var t=localStorage.getItem("ya-theme");if(t)document.documentElement.setAttribute("data-theme",t)}catch(e){}</script>';
    seo_head($seo);
    echo '</head><body>';
    $items = nav_items();
    ?>
<header class="topbar">
  <div class="tb-in">
    <a class="brand" href="<?= e(url('')) ?>"><span class="logo">Y</span><span class="wm">YASSOTA</span></a>
    <form class="tb-search" action="<?= e(url('search')) ?>" method="get" role="search">
      <?= icon('search', 18) ?>
      <input type="search" name="q" placeholder="ابحث عن منشورات، مستخدمين، وسوم…" autocomplete="off" value="<?= e($_GET['q'] ?? '') ?>" id="globalSearch">
      <div class="search-pop" id="searchPop" hidden></div>
    </form>
    <div class="tb-actions">
      <button class="ic-btn theme-toggle" id="themeToggle" aria-label="تبديل المظهر"><?= icon('moon', 20) ?></button>
      <?php if ($me): ?>
        <a class="ic-btn" href="<?= e(url('messages')) ?>" aria-label="الرسائل"><?= icon('chat', 20) ?></a>
        <a class="ic-btn notif-bell" href="<?= e(url('notifications')) ?>" aria-label="الإشعارات"><?= icon('bell', 20) ?><span class="badge" id="notifBadge" hidden></span></a>
        <a class="tb-avatar" href="<?= e(user_url($me['username'])) ?>"><?= avatar_html($me, 34) ?></a>
      <?php else: ?>
        <a class="btn btn-ghost" href="<?= e(url('login')) ?>">دخول</a>
        <a class="btn btn-primary" href="<?= e(url('register')) ?>">حساب جديد</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<div class="shell">
  <aside class="sidebar">
    <nav>
      <?php foreach ($items as $it): ?>
        <a class="nav-link<?= $active === $it[3] ? ' on' : '' ?>" href="<?= e($it[1]) ?>"><?= icon($it[0], 24) ?><span><?= e($it[2]) ?></span></a>
      <?php endforeach; ?>
      <?php if ($me): ?>
        <a class="nav-link<?= $active === 'messages' ? ' on' : '' ?>" href="<?= e(url('messages')) ?>"><?= icon('chat', 24) ?><span>الرسائل</span></a>
        <a class="nav-link<?= $active === 'trending' ? ' on' : '' ?>" href="<?= e(url('trending')) ?>"><?= icon('fire', 24) ?><span>الترند</span></a>
        <?php if ((int)$me['is_admin'] === 1): ?><a class="nav-link<?= $active === 'admin' ? ' on' : '' ?>" href="<?= e(url('admin')) ?>"><?= icon('shield', 24) ?><span>الإدارة</span></a><?php endif; ?>
      <?php endif; ?>
    </nav>
    <?php if ($me): ?>
    <a class="side-me" href="<?= e(user_url($me['username'])) ?>"><?= avatar_html($me, 38) ?><span class="sm-t"><b><?= e($me['name'] ?: $me['username']) ?></b><small>@<?= e($me['username']) ?></small></span></a>
    <?php endif; ?>
    <div class="side-foot">
      <a href="<?= e(url('about')) ?>">عن يسوتا</a> · <a href="<?= e(url('privacy')) ?>">الخصوصية</a> · <a href="<?= e(url('terms')) ?>">الشروط</a><br>
      <a href="<?= e(url('apps/yassota')) ?>">تطبيق أندرويد</a> · © <?= date('Y') ?>
    </div>
  </aside>
  <main class="main">
<?php
}

function layout_bottom(): void {
    $me = current_user(); $items = nav_items();
    ?>
  </main>
</div>

<nav class="bottomnav">
  <?php foreach ($items as $it): ?>
    <a href="<?= e($it[1]) ?>" aria-label="<?= e($it[2]) ?>"><?= icon($it[0], 25) ?></a>
  <?php endforeach; ?>
</nav>

<div class="toast-wrap" id="toasts" aria-live="polite"></div>
<div class="modal-root" id="modalRoot" hidden></div>

<script>window.YA = { base: <?= json_encode(url('')) ?>, api: <?= json_encode(url('api')) ?>, csrf: <?= json_encode(current_user() ? csrf() : '') ?>, me: <?= current_user() ? (int)current_user()['id'] : 'null' ?> };</script>
<script src="<?= e(asset('app.js')) ?>?v=<?= YASSOTA ?>" defer></script>
</body></html>
<?php
}

/* بطاقة منشور في الـFeed — تتوقع أعمدة author_* من JOIN */
function author_of(array $p): array {
    return ['username' => $p['author_username'] ?? '', 'name' => $p['author_name'] ?? '', 'avatar' => $p['author_avatar'] ?? '', 'verified' => $p['author_verified'] ?? 0];
}

function post_card(array $p): void {
    $a = author_of($p);
    $liked = !empty($p['i_liked']); $saved = !empty($p['i_saved']);
    $bg = post_bg($p);
    ?>
  <article class="card post" data-id="<?= (int)$p['id'] ?>">
    <header class="p-head">
      <a class="p-user" href="<?= e(user_url($a['username'])) ?>"><?= avatar_html($a, 42) ?>
        <span class="pu-t"><b><?= e($a['name'] ?: $a['username']) ?><?= verified($a) ?></b><small>@<?= e($a['username']) ?> · <?= e(time_ago($p['created_at'])) ?></small></span>
      </a>
      <button class="ic-btn p-more" data-post-menu="<?= (int)$p['id'] ?>" aria-label="المزيد"><?= icon('more', 20) ?></button>
    </header>
    <a class="p-media" href="<?= e(post_url($p)) ?>"<?= $bg ? ' style="background:' . e($bg) . '"' : '' ?>>
      <?php if ($p['type'] === 'video' && is_real_image($p['video'] ?? '')): ?>
        <span class="play"><?= icon('video', 30) ?></span>
      <?php elseif (is_real_image($p['image'] ?? '')): ?>
        <img loading="lazy" src="<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>">
      <?php else: ?>
        <span class="p-media-txt"><?= e(mb_substr($p['title'], 0, 60)) ?></span>
      <?php endif; ?>
    </a>
    <div class="p-actions">
      <button class="act like<?= $liked ? ' on' : '' ?>" data-act="like" data-id="<?= (int)$p['id'] ?>"><?= icon($liked ? 'heart-fill' : 'heart', 24) ?><span class="c"><?= num_fmt($p['likes_count']) ?></span></button>
      <a class="act" href="<?= e(post_url($p)) ?>#comments"><?= icon('comment', 24) ?><span class="c"><?= num_fmt($p['comments_count']) ?></span></a>
      <button class="act" data-act="share" data-url="<?= e(post_url($p)) ?>" data-title="<?= e($p['title']) ?>"><?= icon('share', 23) ?></button>
      <button class="act save<?= $saved ? ' on' : '' ?>" data-act="save" data-id="<?= (int)$p['id'] ?>" style="margin-inline-start:auto"><?= icon($saved ? 'bookmark-fill' : 'bookmark', 23) ?></button>
    </div>
    <div class="p-body">
      <a class="p-title" href="<?= e(post_url($p)) ?>"><?= e($p['title']) ?></a>
      <?php if (!empty($p['description'])): ?><p class="p-desc"><?= e(mb_substr($p['description'], 0, 160)) ?><?= mb_strlen($p['description']) > 160 ? '…' : '' ?></p><?php endif; ?>
    </div>
  </article>
<?php
}

/* ═══════════════════════ رفع الصور (تحقق + تحسين + WebP) ═══════════════════════ */
function ya_upload_image(array $file, string $subdir = 'posts', int $maxW = 1600): array {
    if (($file['error'] ?? 4) !== 0 || empty($file['tmp_name'])) return ['ok' => false, 'error' => 'لم يتم اختيار صورة.'];
    if ($file['size'] > 8 * 1024 * 1024) return ['ok' => false, 'error' => 'حجم الصورة يتجاوز 8MB.'];
    $info = @getimagesize($file['tmp_name']);
    if (!$info) return ['ok' => false, 'error' => 'ملف الصورة غير صالح.'];
    $mime = $info['mime'];
    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($map[$mime])) return ['ok' => false, 'error' => 'صيغة غير مدعومة (JPG/PNG/WebP/GIF).'];
    $dir = UPLOAD_DIR . '/' . $subdir . '/' . date('Ym');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return ['ok' => false, 'error' => 'تعذّر إنشاء مجلد الرفع — تأكد من صلاحيات uploads/.'];
    $rel = $subdir . '/' . date('Ym') . '/' . bin2hex(random_bytes(8));

    if (function_exists('imagecreatetruecolor') && function_exists('imagewebp') && $mime !== 'image/gif') {
        $src = null;
        if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) $src = @imagecreatefromjpeg($file['tmp_name']);
        elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) $src = @imagecreatefrompng($file['tmp_name']);
        elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($file['tmp_name']);
        if ($src) {
            $w = imagesx($src); $h = imagesy($src);
            $nw = $w; $nh = $h;
            if ($w > $maxW) { $nw = $maxW; $nh = (int)round($h * $maxW / $w); }
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false); imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $out = UPLOAD_DIR . '/' . $rel . '.webp';
            if (@imagewebp($dst, $out, 82)) { imagedestroy($src); imagedestroy($dst); @chmod($out, 0644); return ['ok' => true, 'url' => url('uploads/' . $rel . '.webp')]; }
            imagedestroy($src); imagedestroy($dst);
        }
    }
    $out = UPLOAD_DIR . '/' . $rel . '.' . $map[$mime];
    if (@move_uploaded_file($file['tmp_name'], $out)) { @chmod($out, 0644); return ['ok' => true, 'url' => url('uploads/' . $rel . '.' . $map[$mime])]; }
    return ['ok' => false, 'error' => 'تعذّر حفظ الصورة.'];
}

function ya_parse_tags(string $s): array {
    preg_match_all('/#?([\p{Arabic}\w][\p{Arabic}\w-]{0,40})/u', $s, $m);
    $out = []; foreach ($m[1] as $t) { $t = trim($t); if ($t !== '' && !is_numeric($t)) $out[mb_strtolower($t)] = $t; }
    return array_slice(array_values($out), 0, 15);
}

/* ═══════════════════════ استعلامات الـFeed ═══════════════════════ */
define('YA_PER', 12);

function fetch_feed(string $mode, string $arg = '', int $page = 1): array {
    global $pdo;
    $me = current_user(); $off = max(0, ($page - 1) * YA_PER);
    $sel = "SELECT p.*, u.username author_username, u.name author_name, u.avatar author_avatar, u.verified author_verified
            FROM posts p JOIN users u ON u.id = p.user_id ";
    $where = "WHERE p.status='published' AND u.banned=0 ";
    $params = []; $order = "ORDER BY p.created_at DESC ";

    if ($mode === 'home' && $me) {
        $where .= "AND p.visibility='public' AND (p.user_id = ? OR p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?)) ";
        $params[] = $me['id']; $params[] = $me['id'];
        $cnt = $pdo->prepare("SELECT COUNT(*) c FROM follows WHERE follower_id=?"); $cnt->execute([$me['id']]);
        if ((int)$cnt->fetch()['c'] === 0) { $where = "WHERE p.status='published' AND u.banned=0 AND p.visibility='public' "; $params = []; }
    } elseif ($mode === 'user') {
        $where .= "AND p.user_id = ? "; $params[] = (int)$arg;
        if (!$me || (int)$me['id'] !== (int)$arg) $where .= "AND p.visibility='public' ";
    } elseif ($mode === 'user_saved') {
        $sel = "SELECT p.*, u.username author_username, u.name author_name, u.avatar author_avatar, u.verified author_verified
                FROM post_saves s JOIN posts p ON p.id = s.post_id JOIN users u ON u.id = p.user_id ";
        $where = "WHERE s.user_id = ? AND p.status='published' "; $params = [(int)$arg]; $order = "ORDER BY s.created_at DESC ";
    } elseif ($mode === 'tag') {
        $sel .= "JOIN post_hashtags ph ON ph.post_id = p.id JOIN hashtags h ON h.id = ph.hashtag_id ";
        $where .= "AND p.visibility='public' AND h.slug = ? "; $params[] = $arg;
    } elseif ($mode === 'category') {
        $sel .= "JOIN categories c ON c.id = p.category_id ";
        $where .= "AND p.visibility='public' AND c.slug = ? "; $params[] = $arg;
    } elseif ($mode === 'explore') {
        $where .= "AND p.visibility='public' ";
    } elseif ($mode === 'trending') {
        $where .= "AND p.visibility='public' ";
        $order = "ORDER BY (p.likes_count + p.comments_count*2 + p.saves_count*2 + p.shares_count*3 + p.views*0.1) / POW(TIMESTAMPDIFF(HOUR,p.created_at,NOW())+2,1.4) DESC, p.created_at DESC ";
    } elseif ($mode === 'videos') {
        $where .= "AND p.visibility='public' AND p.type='video' ";
    } elseif ($mode === 'search') {
        $where .= "AND p.visibility='public' AND (p.title LIKE ? OR p.description LIKE ?) ";
        $params[] = '%' . $arg . '%'; $params[] = '%' . $arg . '%';
    } else {
        $where .= "AND p.visibility='public' ";
    }

    $st = $pdo->prepare($sel . $where . $order . "LIMIT " . YA_PER . " OFFSET " . (int)$off);
    $st->execute($params);
    $rows = $st->fetchAll();
    return decorate_posts($rows);
}

function decorate_posts(array $rows): array {
    global $pdo; $me = current_user();
    if (!$me || !$rows) return $rows;
    $ids = array_map(fn($r) => (int)$r['id'], $rows);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $lk = $pdo->prepare("SELECT post_id FROM post_likes WHERE user_id=? AND post_id IN ($in)");
    $lk->execute(array_merge([$me['id']], $ids));
    $liked = array_flip(array_map('intval', $lk->fetchAll(PDO::FETCH_COLUMN)));
    $sv = $pdo->prepare("SELECT post_id FROM post_saves WHERE user_id=? AND post_id IN ($in)");
    $sv->execute(array_merge([$me['id']], $ids));
    $saved = array_flip(array_map('intval', $sv->fetchAll(PDO::FETCH_COLUMN)));
    foreach ($rows as &$r) { $r['i_liked'] = isset($liked[(int)$r['id']]); $r['i_saved'] = isset($saved[(int)$r['id']]); }
    return $rows;
}

function render_cards(array $rows): string {
    ob_start(); foreach ($rows as $p) post_card($p); return ob_get_clean();
}

function render_comment(array $c, bool $isReply = false): void {
    $a = ['username' => $c['author_username'] ?? '', 'name' => $c['author_name'] ?? '', 'avatar' => $c['author_avatar'] ?? '', 'verified' => $c['author_verified'] ?? 0];
    $me = current_user();
    ?>
  <div class="comment" data-cid="<?= (int)$c['id'] ?>"<?= $isReply ? ' style="padding-inline-start:14px"' : '' ?>>
    <a href="<?= e(user_url($a['username'])) ?>"><?= avatar_html($a, $isReply ? 30 : 36) ?></a>
    <div class="cbody">
      <div class="cmeta"><a href="<?= e(user_url($a['username'])) ?>"><b style="color:var(--text)"><?= e($a['name'] ?: $a['username']) ?></b><?= verified($a) ?></a> · <?= e(time_ago($c['created_at'])) ?></div>
      <div class="ctext"><?= nl2br(e($c['body'])) ?></div>
      <div class="cact">
        <?php if (!$isReply): ?><button onclick="yaToggleReply(<?= (int)$c['id'] ?>)">رد</button><?php endif; ?>
        <?php if ($me && ((int)$me['id'] === (int)$c['user_id'] || (int)$me['is_admin'] === 1)): ?><button onclick="yaDelComment(<?= (int)$c['id'] ?>)">حذف</button><?php endif; ?>
        <button onclick="yaReport('comment',<?= (int)$c['id'] ?>)">إبلاغ</button>
      </div>
      <?php if (!$isReply): ?>
      <div data-replies="<?= (int)$c['id'] ?>">
        <?php if (!empty($c['replies'])) foreach ($c['replies'] as $r) render_comment($r, true); ?>
      </div>
      <form class="cform" data-post="<?= (int)$c['post_id'] ?>" data-parent="<?= (int)$c['id'] ?>" onsubmit="return yaComment(this)" data-reply-form="<?= (int)$c['id'] ?>" hidden style="position:static">
        <textarea class="input" rows="1" placeholder="اكتب رداً…" style="min-height:auto"></textarea>
        <button class="btn btn-primary" type="submit"><?= icon('send', 18) ?></button>
      </form>
      <?php endif; ?>
    </div>
  </div>
<?php
}

function fetch_comments(PDO $pdo, int $postId): array {
    $st = $pdo->prepare("SELECT c.*, u.username author_username, u.name author_name, u.avatar author_avatar, u.verified author_verified
        FROM comments c JOIN users u ON u.id = c.user_id WHERE c.post_id = ? ORDER BY c.created_at ASC");
    $st->execute([$postId]);
    $all = $st->fetchAll(); $tops = []; $replies = [];
    foreach ($all as $c) {
        if ($c['parent_id']) { $replies[(int)$c['parent_id']][] = $c; }
        else { $c['replies'] = []; $tops[(int)$c['id']] = $c; }
    }
    foreach ($replies as $pid => $rs) if (isset($tops[$pid])) $tops[$pid]['replies'] = $rs;
    return array_reverse(array_values($tops));
}
function render_tiles(array $rows): string {
    ob_start(); foreach ($rows as $p) explore_tile($p); return ob_get_clean();
}

/* شبكة استكشاف مصغّرة */
function explore_tile(array $p): void {
    $bg = post_bg($p);
    ?>
  <a class="ex-tile" href="<?= e(post_url($p)) ?>"<?= $bg ? ' style="background:' . e($bg) . '"' : '' ?>>
    <?php if (is_real_image($p['image'] ?? '')): ?><img loading="lazy" src="<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>"><?php else: ?><span class="ex-txt"><?= e(mb_substr($p['title'], 0, 48)) ?></span><?php endif; ?>
    <span class="ex-ov"><?= icon('heart-fill', 16) ?> <?= num_fmt($p['likes_count']) ?><?= $p['type'] === 'video' ? ' ' . icon('video', 16) : '' ?></span>
  </a>
<?php
}
