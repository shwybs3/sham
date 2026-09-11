<?php
require __DIR__ . '/config.php';
$me = require_login();

$st = $pdo->prepare("SELECT n.*, a.username actor_username, a.name actor_name, a.avatar actor_avatar, a.verified actor_verified,
    p.slug post_slug, p.title post_title FROM notifications n
    LEFT JOIN users a ON a.id=n.actor_id LEFT JOIN posts p ON p.id=n.post_id
    WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 60");
$st->execute([$me['id']]);
$rows = $st->fetchAll();
$pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0")->execute([$me['id']]);

$verb = ['like' => 'أعجب بمنشورك', 'comment' => 'علّق على منشورك', 'reply' => 'ردّ على تعليقك', 'follow' => 'بدأ بمتابعتك', 'message' => 'أرسل لك رسالة', 'mention' => 'أشار إليك'];

layout_top(['title' => 'الإشعارات | ' . setting('site_name', 'YASSOTA'), 'noindex' => true, 'active' => 'notifications']);
?>
<div class="phead"><div><h1>الإشعارات</h1><p class="sub">آخر التفاعلات على حسابك</p></div></div>
<?php if (!$rows): ?>
  <div class="empty"><?= icon('bell',44) ?><p>لا إشعارات بعد.</p></div>
<?php else: ?>
  <div class="feed" style="gap:2px">
  <?php foreach ($rows as $n):
    $actor = ['username' => $n['actor_username'] ?: 'yassota', 'name' => $n['actor_name'] ?: 'يسوتا', 'avatar' => $n['actor_avatar'] ?: '', 'verified' => $n['actor_verified'] ?? 0];
    $link = $n['type'] === 'follow' ? user_url($actor['username']) : ($n['type'] === 'message' ? url('messages?to=' . $actor['username']) : ($n['post_slug'] ? url('post/' . $n['post_slug']) : '#'));
  ?>
    <a class="card" style="display:flex;align-items:center;gap:12px;padding:12px 14px<?= (int)$n['is_read']===0?';border-inline-start:3px solid var(--brand)':'' ?>" href="<?= e($link) ?>">
      <?= avatar_html($actor, 42) ?>
      <div style="flex:1;min-width:0"><span><b><?= e($actor['name']) ?></b><?= verified($actor) ?> <?= e($verb[$n['type']] ?? 'تفاعل') ?></span>
        <?php if ($n['post_title']): ?><div style="color:var(--faint);font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">«<?= e($n['post_title']) ?>»</div><?php endif; ?></div>
      <small style="color:var(--faint)"><?= e(time_ago($n['created_at'])) ?></small>
    </a>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php layout_bottom(); ?>
