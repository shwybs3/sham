<?php
/**
 * ═══════════════════════════════════════════════
 * Long-form content upgrade for the starter articles and tools.
 *
 * The original seeders (seed_articles.php, seed_tools.php,
 * seed_pro_tools.php) use INSERT IGNORE so they're safe to re-run without
 * clobbering anything — but that also means they can never be used to
 * push updated body copy to a site that already has these rows. This
 * file is the opposite: small, explicit UPDATE calls keyed by slug, run
 * from an admin button, so re-running is always safe (it just re-applies
 * the same long-form text) while still actually reaching a live database.
 *
 * Batches are added incrementally — each expand_articles_batchN() /
 * expand_tools_batchN() covers a handful of items so the admin page can
 * show real progress instead of one giant all-or-nothing action.
 * ═══════════════════════════════════════════════
 */

/** Updates one article's body (and recomputed reading time) by slug. Silently
 *  no-ops if the slug doesn't exist yet (fresh installs seed first). */
function expand_article(PDO $pdo, string $slug, string $html): void {
    $minutes = max(4, (int)ceil(str_word_count(strip_tags($html)) / 220));
    $pdo->prepare("UPDATE articles SET body = ?, reading_time = ? WHERE slug = ?")
        ->execute([$html, $minutes, $slug]);
}

/** Updates one tool's full_description by slug. */
function expand_tool(PDO $pdo, string $slug, string $html): void {
    $pdo->prepare("UPDATE tools SET full_description = ? WHERE slug = ?")
        ->execute([$html, $slug]);
}

/** Registry of available batches: key => [label, callable, kind]. Each
 *  admin page entry shows real coverage stats computed against the DB. */
function content_expansion_batches(): array {
    $batches = [];
    foreach (glob(__DIR__ . '/../seed/expand_articles_batch*.php') as $f) require_once $f;
    foreach (glob(__DIR__ . '/../seed/expand_tools_batch*.php') as $f) require_once $f;

    for ($i = 1; $i <= 20; $i++) {
        $fn = "expand_articles_batch{$i}";
        if (function_exists($fn)) $batches["articles_$i"] = ["Articles — batch $i", $fn, 'article'];
    }
    for ($i = 1; $i <= 20; $i++) {
        $fn = "expand_tools_batch{$i}";
        if (function_exists($fn)) $batches["tools_$i"] = ["Tools — batch $i", $fn, 'tool'];
    }
    return $batches;
}
