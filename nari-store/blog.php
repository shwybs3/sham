<?php
require_once __DIR__ . '/partials.php';

$articles = nari_read_json_file(__DIR__ . '/data/articles.json');
$articles = array_values(array_filter($articles, fn($a) => !empty($a['published'])));
usort($articles, fn($a, $b) => strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));

$jsonld = [json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'المقالات والأخبار — ناري ستور',
    'url' => nari_site_url() . '/blog',
    'inLanguage' => 'ar',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];

nari_head([
    'title' => 'المقالات والأخبار | ناري ستور',
    'description' => 'مقالات وشروحات وتحديثات وأخبار موثوقة من ناري ستور المتعلقة بخدماتنا الرقمية.',
    'keywords' => 'مقالات ناري ستور, أخبار المتجر, شروحات رقمية',
    'path' => 'blog',
    'active_nav' => 'blog',
    'crumbs' => [['label' => 'المقالات والأخبار']],
    'jsonld' => $jsonld,
]);
?>
<section class="hero" style="padding-bottom:32px;">
  <div class="wrap">
    <p class="eyebrow">المدوّنة</p>
    <h1>المقالات <em>والأخبار</em></h1>
    <p class="lead">تحديثات وشروحات وأخبار موثوقة تتعلق بخدمات ناري ستور، تُنشر مباشرة من فريق المتجر.</p>
  </div>
</section>

<section style="padding-top:10px;">
  <div class="wrap">
    <?php if (empty($articles)): ?>
      <p style="color:var(--muted); text-align:center; padding:40px 0;">لا توجد مقالات منشورة بعد.</p>
    <?php else: ?>
      <div class="service-cards grid-3">
        <?php foreach ($articles as $a): ?>
          <a class="service-card" href="article/<?= rawurlencode($a['slug']) ?>">
            <span class="card-art"><img src="assets/art/hero.svg" alt="<?= nari_esc($a['title']) ?>" width="480" height="320" loading="lazy"></span>
            <span class="card-body">
              <span class="tag"><?= nari_esc($a['category'] ?? 'مقال') ?></span>
              <h3><?= nari_esc($a['title']) ?></h3>
              <p><?= nari_esc($a['excerpt'] ?? '') ?></p>
              <span class="go">اقرأ المزيد <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="11,6 5,12 11,18"/></svg></span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="cta-band">
  <div class="wrap">
    <h2>لديك سؤال عن خدماتنا؟</h2>
    <p>تواصل معنا مباشرة عبر واتساب وسنجيبك بأسرع وقت.</p>
    <a class="btn btn-ghost btn-lg" data-wa="مرحباً، لدي سؤال عن ناري ستور" href="#"><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a9 9 0 00-7.8 13.5L3 21l4.6-1.2A9 9 0 1012 3z"/><path d="M8.6 8.6c-.3.9 0 2.2 1.5 3.7 1.5 1.5 2.9 1.9 3.8 1.6.5-.1.9-.6 1-1l.1-.5-1.7-1-.5.6c-.2.3-.5.3-.9.1-.6-.3-1.2-.9-1.6-1.5-.2-.4-.2-.7.1-.9l.5-.5-1-1.7-.3.1z"/></svg> تواصل معنا</a>
  </div>
</section>
<?php nari_footer(); ?>
