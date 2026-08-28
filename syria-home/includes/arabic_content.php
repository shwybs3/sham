<?php
/**
 * Arabic translation seed batches — same pattern as content_expansion.php's
 * article/tool batches, but INSERT rather than UPDATE: each batch adds a
 * handful of new Arabic rows, each linked via translation_of to the
 * existing English article/tool it's a translation of.
 *
 * Batches are auto-discovered from seed/seed_articles_ar_batch*.php and
 * seed/seed_tools_ar_batch*.php, run from the admin "Arabic Content" page.
 * Every insert is keyed by the Arabic row's own slug with INSERT IGNORE,
 * so re-running a batch is always safe.
 */

/** Looks up an English article's id by slug — used to set translation_of. */
function ar_find_article_id(PDO $pdo, string $enSlug): ?int {
    $stmt = $pdo->prepare("SELECT id FROM articles WHERE slug = ? AND lang = 'en'");
    $stmt->execute([$enSlug]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int)$id : null;
}

/** Looks up an English tool's id by slug — used to set translation_of. */
function ar_find_tool_id(PDO $pdo, string $enSlug): ?int {
    $stmt = $pdo->prepare("SELECT id FROM tools WHERE slug = ? AND lang = 'en'");
    $stmt->execute([$enSlug]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int)$id : null;
}

/** Inserts one Arabic article row, translating $enSlug. No-ops if $enSlug
 *  doesn't exist yet or this Arabic slug was already inserted. */
function ar_seed_article(PDO $pdo, string $enSlug, string $arSlug, array $a): void {
    $translationOf = ar_find_article_id($pdo, $enSlug);
    if (!$translationOf) return;
    $catId = null;
    if (!empty($a['cat'])) {
        $c = $pdo->prepare("SELECT category_id FROM articles WHERE id = ?");
        $c->execute([$translationOf]);
        $catId = $c->fetchColumn() ?: null;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO articles
        (title, slug, category_id, content_type, excerpt, body, hero_icon, hero_gradient, meta_title, meta_description, meta_keywords, tags, author, status, trending, reading_time, published_at, lang, translation_of)
        VALUES (:title,:slug,:category_id,:content_type,:excerpt,:body,:hero_icon,:hero_gradient,:meta_title,:meta_description,:meta_keywords,:tags,:author,'published',:trending,:reading_time,NOW(),'ar',:translation_of)");
    $stmt->execute([
        'title' => $a['title'], 'slug' => $arSlug, 'category_id' => $catId,
        'content_type' => $a['type'] ?? 'article', 'excerpt' => $a['excerpt'],
        'body' => $a['body'], 'hero_icon' => $a['icon'] ?? 'fa-newspaper', 'hero_gradient' => $a['gradient'] ?? 'g1',
        'meta_title' => $a['title'], 'meta_description' => $a['excerpt'], 'meta_keywords' => $a['meta_kw'] ?? '',
        'tags' => $a['tags'] ?? '', 'author' => $a['author'] ?? 'فريق التحرير', 'trending' => $a['trending'] ?? 0,
        'reading_time' => max(4, (int)ceil(str_word_count(strip_tags($a['body'])) / 180)),
        'translation_of' => $translationOf,
    ]);
}

/** Inserts one Arabic tool row, translating $enSlug (same tool_key — the
 *  interactive engine is language-agnostic, only the copy is translated). */
function ar_seed_tool(PDO $pdo, string $enSlug, string $arSlug, array $t): void {
    $translationOf = ar_find_tool_id($pdo, $enSlug);
    if (!$translationOf) return;
    $en = $pdo->prepare("SELECT category_id, tool_key, icon_class FROM tools WHERE id = ?");
    $en->execute([$translationOf]);
    $enRow = $en->fetch();
    if (!$enRow) return;
    $stmt = $pdo->prepare("INSERT IGNORE INTO tools
        (name, slug, category_id, icon_class, tool_key, short_description, full_description, meta_title, meta_description, meta_keywords, status, lang, translation_of)
        VALUES (:name,:slug,:category_id,:icon_class,:tool_key,:short_description,:full_description,:meta_title,:meta_description,:meta_keywords,'published','ar',:translation_of)");
    $stmt->execute([
        'name' => $t['name'], 'slug' => $arSlug, 'category_id' => $enRow['category_id'],
        'icon_class' => $enRow['icon_class'], 'tool_key' => $enRow['tool_key'],
        'short_description' => $t['short'], 'full_description' => $t['full'],
        'meta_title' => $t['name'], 'meta_description' => $t['short'], 'meta_keywords' => $t['meta_kw'] ?? '',
        'translation_of' => $translationOf,
    ]);
}

/** Registry of available Arabic-translation batches, same shape as
 *  content_expansion_batches(): key => [label, callable]. */
function arabic_content_batches(): array {
    $batches = [];
    foreach (glob(__DIR__ . '/../seed/seed_articles_ar_batch*.php') as $f) require_once $f;
    foreach (glob(__DIR__ . '/../seed/seed_tools_ar_batch*.php') as $f) require_once $f;

    for ($i = 1; $i <= 20; $i++) {
        $fn = "seed_articles_ar_batch{$i}";
        if (function_exists($fn)) $batches["articles_ar_$i"] = ["Arabic articles — batch $i", $fn];
    }
    for ($i = 1; $i <= 20; $i++) {
        $fn = "seed_tools_ar_batch{$i}";
        if (function_exists($fn)) $batches["tools_ar_$i"] = ["Arabic tools — batch $i", $fn];
    }
    return $batches;
}
