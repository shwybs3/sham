<?php
require __DIR__ . '/config.php';
$me = current_user();
$slug = $_GET['slug'] ?? '';

$st = $pdo->prepare("SELECT p.*, u.username author_username, u.name author_name, u.avatar author_avatar,
    u.verified author_verified, u.bio author_bio, u.id author_id, c.name cat_name, c.slug cat_slug
    FROM posts p JOIN users u ON u.id = p.user_id LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.slug = ? LIMIT 1");
$st->execute([$slug]);
$p = $st->fetch();

$visible = $p && ($p['status'] === 'published' && $p['visibility'] === 'public'
    || ($me && ((int)$me['id'] === (int)$p['user_id'] || (int)$me['is_admin'] === 1)));
if (!$visible) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

if (empty($_SESSION['viewed'][$p['id']])) {
    $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$p['id']]);
    $_SESSION['viewed'][$p['id']] = 1; $p['views']++;
}
[$p] = decorate_posts([$p]);

$tags = $pdo->prepare("SELECT h.name, h.slug FROM post_hashtags ph JOIN hashtags h ON h.id = ph.hashtag_id WHERE ph.post_id = ?");
$tags->execute([$p['id']]); $tags = $tags->fetchAll();

$comments = fetch_comments($pdo, (int)$p['id']);

$related = [];
if ($p['category_id']) {
    $rs = $pdo->prepare("SELECT p.*, u.username author_username, u.name author_name, u.avatar author_avatar, u.verified author_verified
        FROM posts p JOIN users u ON u.id=p.user_id WHERE p.category_id=? AND p.id<>? AND p.status='published' AND p.visibility='public' ORDER BY p.created_at DESC LIMIT 4");
    $rs->execute([$p['category_id'], $p['id']]); $related = $rs->fetchAll();
}
$byAuthor = $pdo->prepare("SELECT slug, title, image FROM posts WHERE user_id=? AND id<>? AND status='published' AND visibility='public' ORDER BY created_at DESC LIMIT 4");
$byAuthor->execute([$p['user_id'], $p['id']]); $byAuthor = $byAuthor->fetchAll();

$iFollow = false;
if ($me && (int)$me['id'] !== (int)$p['user_id']) {
    $f = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id=? AND following_id=?"); $f->execute([$me['id'], $p['user_id']]);
    $iFollow = (bool)$f->fetch();
}

$ogimg = is_real_image($p['image']) ? $p['image'] : (setting('og_image') ?: '');
$desc = $p['seo_description'] ?: mb_substr(strip_tags($p['description']), 0, 155);
$authorName = $p['author_name'] ?: $p['author_username'];

$jsonld = [[
    '@context' => 'https://schema.org', '@type' => $p['type'] === 'video' ? 'VideoObject' : 'Article',
    'headline' => $p['title'], 'description' => $desc,
    'author' => ['@type' => 'Person', 'name' => $authorName, 'url' => user_url($p['author_username'])],
    'datePublished' => date('c', strtotime($p['created_at'])), 'dateModified' => date('c', strtotime($p['updated_at'])),
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => post_url($p)],
    'publisher' => ['@type' => 'Organization', 'name' => setting('site_name', 'YASSOTA'), 'url' => url('')],
    'interactionStatistic' => [
        ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/LikeAction', 'userInteractionCount' => (int)$p['likes_count']],
        ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/CommentAction', 'userInteractionCount' => (int)$p['comments_count']],
    ],
]];
if (is_real_image($ogimg)) { $jsonld[0]['image'] = [$ogimg]; if ($p['type'] === 'video') { $jsonld[0]['thumbnailUrl'] = $ogimg; $jsonld[0]['uploadDate'] = date('c', strtotime($p['created_at'])); } }
if ($p['type'] === 'video' && is_real_image($p['video'])) $jsonld[0]['contentUrl'] = $p['video'];
$crumbs = [['name' => 'الرئيسية', 'url' => url('')]];
if ($p['cat_slug']) $crumbs[] = ['name' => $p['cat_name'], 'url' => category_url($p['cat_slug'])];
$crumbs[] = ['name' => $p['title'], 'url' => post_url($p)];
$jsonld[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
    'itemListElement' => array_map(fn($i, $c) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url']], array_keys($crumbs), $crumbs)];

layout_top([
    'title' => ($p['seo_title'] ?: $p['title']) . ' | ' . setting('site_name', 'YASSOTA'),
    'description' => $desc, 'keywords' => $p['seo_keywords'],
    'canonical' => post_url($p), 'image' => $ogimg, 'og_type' => 'article',
    'jsonld' => $jsonld, 'active' => '',
]);
$bg = post_bg($p);
?>
<nav aria-label="مسار" style="font-size:13px;color:var(--muted);margin-bottom:8px">
  <a href="<?= e(url('')) ?>">الرئيسية</a><?php if ($p['cat_slug']): ?> › <a href="<?= e(category_url($p['cat_slug'])) ?>"><?= e($p['cat_name']) ?></a><?php endif; ?>
</nav>

<article class="article">
  <header class="p-head" style="padding:0 0 4px">
    <a class="p-user" href="<?= e(user_url($p['author_username'])) ?>"><?= avatar_html(['username'=>$p['author_username'],'name'=>$p['author_name'],'avatar'=>$p['author_avatar']], 46) ?>
      <span class="pu-t"><b><?= e($authorName) ?><?= verified(['verified'=>$p['author_verified']]) ?></b><small>@<?= e($p['author_username']) ?> · <?= e(time_ago($p['created_at'])) ?> · <?= num_fmt($p['views']) ?> مشاهدة</small></span>
    </a>
    <?php if (!$me || (int)$me['id'] !== (int)$p['user_id']): ?>
      <button class="btn btn-primary<?= $iFollow ? ' on' : '' ?>" data-act="follow" data-id="<?= (int)$p['user_id'] ?>" style="margin-inline-start:auto"><?= $iFollow ? 'إلغاء المتابعة' : 'متابعة' ?></button>
    <?php endif; ?>
  </header>

  <h1 class="a-title"><?= e($p['title']) ?></h1>

  <?php if ($p['type'] === 'video' && is_real_image($p['video'])): ?>
    <video class="p-media" style="border-radius:var(--r)" controls preload="metadata" playsinline<?= is_real_image($p['image']) ? ' poster="' . e($p['image']) . '"' : '' ?>>
      <source src="<?= e($p['video']) ?>"></video>
  <?php elseif (is_real_image($p['image'])): ?>
    <img class="p-media" style="border-radius:var(--r);aspect-ratio:auto" src="<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>">
  <?php elseif ($bg): ?>
    <div class="p-media" style="border-radius:var(--r);background:<?= e($bg) ?>"><span class="p-media-txt"><?= e(mb_substr($p['title'],0,80)) ?></span></div>
  <?php endif; ?>

  <div class="p-actions" style="padding:8px 0">
    <button class="act like<?= !empty($p['i_liked']) ? ' on' : '' ?>" data-act="like" data-id="<?= (int)$p['id'] ?>"><?= icon(!empty($p['i_liked'])?'heart-fill':'heart',24) ?><span class="c"><?= num_fmt($p['likes_count']) ?></span></button>
    <a class="act" href="#comments"><?= icon('comment',24) ?><span class="c" id="commentCount"><?= num_fmt($p['comments_count']) ?></span></a>
    <button class="act" data-act="share" data-url="<?= e(post_url($p)) ?>" data-title="<?= e($p['title']) ?>"><?= icon('share',23) ?></button>
    <button class="act save<?= !empty($p['i_saved']) ? ' on' : '' ?>" data-act="save" data-id="<?= (int)$p['id'] ?>" style="margin-inline-start:auto"><?= icon(!empty($p['i_saved'])?'bookmark-fill':'bookmark',23) ?></button>
  </div>

  <?php if (trim($p['description']) !== ''): ?>
    <div class="a-content"><?php foreach (preg_split('/\n{2,}/', trim($p['description'])) as $para) echo '<p>' . nl2br(e($para)) . '</p>'; ?></div>
  <?php endif; ?>

  <?php if ($tags): ?>
  <div class="a-tags"><?php foreach ($tags as $t): ?><a class="chip" href="<?= e(tag_url($t['slug'])) ?>">#<?= e($t['name']) ?></a><?php endforeach; ?></div>
  <?php endif; ?>

  <?php if ($p['location']): ?><p style="color:var(--muted);font-size:13.5px"><?= icon('location',16) ?> <?= e($p['location']) ?></p><?php endif; ?>

  <section id="comments" style="margin-top:24px">
    <h2 class="rel-h">التعليقات (<?= num_fmt($p['comments_count']) ?>)</h2>
    <?php if ($me): ?>
    <form class="cform" data-post="<?= (int)$p['id'] ?>" onsubmit="return yaComment(this)">
      <?= avatar_html($me, 36) ?>
      <textarea class="input" rows="1" placeholder="أضف تعليقاً…"></textarea>
      <button class="btn btn-primary" type="submit"><?= icon('send',18) ?></button>
    </form>
    <?php else: ?>
    <p style="color:var(--muted)"><a href="<?= e(url('login')) ?>" style="color:var(--brand-ink)">سجّل الدخول</a> للمشاركة في التعليقات.</p>
    <?php endif; ?>
    <div id="commentList">
      <?php if ($comments) foreach ($comments as $c) render_comment($c); else echo '<p class="empty" style="padding:24px">لا توجد تعليقات بعد — كن أول من يعلّق.</p>'; ?>
    </div>
  </section>

  <?php if ($related): ?>
  <h2 class="rel-h">منشورات مشابهة</h2>
  <div class="ex-grid"><?php echo render_tiles(decorate_posts($related)); ?></div>
  <?php endif; ?>

  <?php if ($byAuthor): ?>
  <h2 class="rel-h">المزيد من <?= e($authorName) ?></h2>
  <div class="ex-grid"><?php foreach ($byAuthor as $b): $bp = $b + ['likes_count'=>0,'type'=>'image','slug'=>$b['slug']]; explore_tile($bp); endforeach; ?></div>
  <?php endif; ?>
</article>

<script>
window.yaToggleReply = function (id) { var f = document.querySelector('[data-reply-form="' + id + '"]'); if (f) { f.hidden = !f.hidden; if (!f.hidden) f.querySelector('textarea').focus(); } };
window.yaDelComment = function (id) {
  if (!confirm('حذف التعليق؟')) return;
  yaApi('comment_delete', { id: id }).then(function (r) {
    if (r.ok) { var el = document.querySelector('.comment[data-cid="' + id + '"]'); if (el) el.remove(); yaToast('تم الحذف'); }
    else yaToast(r.error || 'تعذّر الحذف', 'err');
  });
};
</script>
<?php layout_bottom(); ?>
