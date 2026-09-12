<?php
require __DIR__ . '/config.php';
$me = current_user();
$username = $_GET['u'] ?? '';

$st = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$st->execute([$username]);
$u = $st->fetch();
if (!$u || (int)$u['banned'] === 1) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$isSelf = $me && (int)$me['id'] === (int)$u['id'];
$tab = $_GET['tab'] ?? 'posts';
$iFollow = false;
if ($me && !$isSelf) {
    $f = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id=? AND following_id=?"); $f->execute([$me['id'], $u['id']]);
    $iFollow = (bool)$f->fetch();
}

$people = null;
if ($tab === 'followers' || $tab === 'following') {
    if ($tab === 'followers') {
        $ps = $pdo->prepare("SELECT u.* FROM follows f JOIN users u ON u.id = f.follower_id
            WHERE f.following_id = ? AND u.banned = 0 ORDER BY f.created_at DESC LIMIT 100");
    } else {
        $ps = $pdo->prepare("SELECT u.* FROM follows f JOIN users u ON u.id = f.following_id
            WHERE f.follower_id = ? AND u.banned = 0 ORDER BY f.created_at DESC LIMIT 100");
    }
    $ps->execute([$u['id']]);
    $people = $ps->fetchAll();
    $rows = [];
} else {
    $rows = fetch_feed('user', (string)$u['id'], 1);
    if ($tab === 'saved' && $isSelf) $rows = fetch_feed('user_saved', (string)$u['id'], 1);
    if ($tab === 'videos') $rows = array_values(array_filter($rows, fn($r) => $r['type'] === 'video'));
}

$name = $u['name'] ?: $u['username'];
$bio = $u['bio'] ?: ('حساب ' . $name . ' على يسوتا');
layout_top([
    'title' => $name . ' (@' . $u['username'] . ') | ' . setting('site_name', 'YASSOTA'),
    'description' => mb_substr($bio, 0, 155), 'canonical' => user_url($u['username']),
    'image' => is_real_image($u['avatar']) ? $u['avatar'] : '', 'og_type' => 'profile',
    'noindex' => (int)$u['is_private'] === 1,
    'active' => $isSelf ? 'profile' : '',
    'jsonld' => [[
        '@context' => 'https://schema.org', '@type' => 'ProfilePage',
        'mainEntity' => ['@type' => 'Person', 'name' => $name, 'alternateName' => '@' . $u['username'],
            'description' => $bio, 'url' => user_url($u['username']),
            'image' => is_real_image($u['avatar']) ? $u['avatar'] : url('assets/favicon.svg'),
            'interactionStatistic' => ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/FollowAction', 'userInteractionCount' => (int)$u['followers_count']]],
    ]],
]);
$cover = is_real_image($u['cover']) ? 'background-image:url(' . e($u['cover']) . ');background-size:cover' : '';
?>
<div class="profile-cover" style="<?= $cover ?>"></div>
<div class="profile-head">
  <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px">
    <span class="p-av-lg" style="display:inline-grid;place-items:center;overflow:hidden;background:<?= e(avatar_bg($u)) ?>"><?= avatar_html($u, 96) ?></span>
    <div style="display:flex;gap:8px;padding-bottom:6px">
      <?php if ($isSelf): ?>
        <a class="btn btn-ghost" href="<?= e(url('settings')) ?>"><?= icon('settings',18) ?> تعديل</a>
      <?php else: ?>
        <button class="btn btn-primary<?= $iFollow ? ' on' : '' ?>" data-act="follow" data-id="<?= (int)$u['id'] ?>"><?= $iFollow ? 'إلغاء المتابعة' : 'متابعة' ?></button>
        <?php if ($me): ?><a class="btn btn-ghost" href="<?= e(url('messages?to=' . $u['username'])) ?>"><?= icon('chat',18) ?></a><?php endif; ?>
        <button class="ic-btn btn-ghost" onclick="yaReport('user',<?= (int)$u['id'] ?>)" aria-label="إبلاغ"><?= icon('flag',18) ?></button>
      <?php endif; ?>
    </div>
  </div>
  <div class="p-meta">
    <div class="p-name"><?= e($name) ?><?= verified($u) ?></div>
    <div class="p-handle">@<?= e($u['username']) ?><?= (int)$u['is_admin']===1 ? ' · <span style="color:var(--brand-ink)">فريق يسوتا</span>' : '' ?></div>
    <?php if ($u['bio']): ?><p class="p-bio"><?= nl2br(e($u['bio'])) ?></p><?php endif; ?>
    <div class="p-links">
      <?php if ($u['website']): ?><a href="<?= e($u['website']) ?>" rel="nofollow noopener" target="_blank"><?= icon('link',15) ?> <?= e(preg_replace('~^https?://~','',$u['website'])) ?></a><?php endif; ?>
      <?php if ($u['location']): ?><span><?= icon('location',15) ?> <?= e($u['location']) ?></span><?php endif; ?>
      <span><?= icon('bell',15) ?> انضم <?= e(date('Y/m', strtotime($u['created_at']))) ?></span>
    </div>
    <div class="p-stats">
      <div><b><?= num_fmt($u['posts_count']) ?></b><span>منشور</span></div>
      <a href="<?= e(user_url($u['username']) . '?tab=followers') ?>"><b><?= num_fmt($u['followers_count']) ?></b><span>متابِع</span></a>
      <a href="<?= e(user_url($u['username']) . '?tab=following') ?>"><b><?= num_fmt($u['following_count']) ?></b><span>يتابع</span></a>
    </div>
  </div>
  <div class="tabs">
    <a class="tab<?= $tab==='posts'?' on':'' ?>" href="<?= e(user_url($u['username'])) ?>">المنشورات</a>
    <a class="tab<?= $tab==='videos'?' on':'' ?>" href="<?= e(user_url($u['username']).'?tab=videos') ?>">الفيديوهات</a>
    <?php if ($isSelf): ?><a class="tab<?= $tab==='saved'?' on':'' ?>" href="<?= e(user_url($u['username']).'?tab=saved') ?>">المحفوظات</a><?php endif; ?>
  </div>
</div>

<?php if ($people !== null): ?>
  <?php if ($people): $fset = my_following_ids(); ?>
    <div class="ulist"><?php foreach ($people as $pu) user_row($pu, isset($fset[(int)$pu['id']])); ?></div>
  <?php else: ?>
    <div class="empty"><?= icon('user',44) ?><p><?= $tab === 'followers' ? 'لا متابعين بعد.' : 'لا يتابع أحداً بعد.' ?></p></div>
  <?php endif; ?>
<?php elseif ((int)$u['is_private'] === 1 && !$isSelf && !$iFollow): ?>
  <div class="empty"><?= icon('lock',44) ?><p>هذا الحساب خاص. تابعه لرؤية منشوراته.</p></div>
<?php elseif ($rows): ?>
  <div class="ex-grid"><?= render_tiles($rows) ?></div>
<?php else: ?>
  <div class="empty"><?= icon('grid',44) ?><p><?= $isSelf ? 'لم تنشر شيئاً بعد.' : 'لا منشورات بعد.' ?> <?php if ($isSelf): ?><a href="<?= e(url('create')) ?>" style="color:var(--brand-ink)">أنشئ منشوراً</a><?php endif; ?></p></div>
<?php endif; ?>

<?php layout_bottom(); ?>
