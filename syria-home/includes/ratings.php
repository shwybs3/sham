<?php
/**
 * ═══════════════════════════════════════════════
 * Visitor ratings — the data behind the star snippets.
 *
 * Google's structured-data policy requires review markup to reflect
 * ratings genuinely collected from users. So the JSON-LD emitted here is
 * built from the `ratings` table only, and is omitted entirely until a
 * page has real votes. Nothing is seeded, defaulted or invented.
 * ═══════════════════════════════════════════════
 */

/** Minimum real votes before star markup is emitted at all. */
const SH_RATING_MIN_VOTES = 3;

/** Stable per-visitor fingerprint. Salted with APP_SECRET so the table
 *  never stores a reversible IP. */
function rating_fingerprint(): string {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip = trim(explode(',', $ip)[0]);
    return hash('sha256', $ip . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . APP_SECRET);
}

/** ['avg' => float, 'count' => int] for one entity. */
function rating_summary(string $type, int $id): array {
    global $pdo;
    static $cache = [];
    $ck = $type . ':' . $id;
    if (isset($cache[$ck])) return $cache[$ck];

    try {
        $st = $pdo->prepare("SELECT AVG(rating) AS a, COUNT(*) AS c FROM ratings WHERE entity_type=? AND entity_id=?");
        $st->execute([$type, $id]);
        $row = $st->fetch();
        $out = ['avg' => round((float)($row['a'] ?? 0), 1), 'count' => (int)($row['c'] ?? 0)];
    } catch (PDOException $e) {
        $out = ['avg' => 0.0, 'count' => 0];
    }
    return $cache[$ck] = $out;
}

/** The current visitor's own vote, or 0 if they haven't rated this yet. */
function rating_mine(string $type, int $id): int {
    global $pdo;
    try {
        $st = $pdo->prepare("SELECT rating FROM ratings WHERE entity_type=? AND entity_id=? AND ip_hash=?");
        $st->execute([$type, $id, rating_fingerprint()]);
        return (int)$st->fetchColumn();
    } catch (PDOException $e) { return 0; }
}

/** Casts or updates a vote. Returns the fresh summary. */
function rating_cast(string $type, int $id, int $stars): array {
    global $pdo;
    $stars = max(1, min(5, $stars));
    if (!in_array($type, ['article', 'tool', 'product'], true)) return ['avg' => 0.0, 'count' => 0];

    $pdo->prepare("INSERT INTO ratings (entity_type, entity_id, rating, ip_hash) VALUES (?,?,?,?)
                   ON DUPLICATE KEY UPDATE rating = VALUES(rating), created_at = NOW()")
        ->execute([$type, $id, $stars, rating_fingerprint()]);

    return rating_summary($type, $id);
}

/**
 * AggregateRating JSON-LD fragment — or null when there aren't enough real
 * votes yet. Callers merge the result into their page's JSON-LD only if
 * it isn't null, so pages without genuine ratings carry no review markup.
 */
function rating_jsonld(string $type, int $id): ?array {
    $s = rating_summary($type, $id);
    if ($s['count'] < SH_RATING_MIN_VOTES || $s['avg'] <= 0) return null;
    return [
        '@type'       => 'AggregateRating',
        'ratingValue' => number_format($s['avg'], 1, '.', ''),
        'ratingCount' => $s['count'],
        'bestRating'  => '5',
        'worstRating' => '1',
    ];
}

/** Interactive star widget. Posts to rate.php and updates in place. */
function rating_widget(string $type, int $id): void {
    if ((int)setting('ratings_enabled', 1) !== 1) return;
    $s    = rating_summary($type, $id);
    $mine = rating_mine($type, $id);
    $uid  = $type . '-' . $id;
    ?>
    <div class="rating-box" id="rate-<?= e($uid) ?>" data-type="<?= e($type) ?>" data-id="<?= (int)$id ?>">
      <div class="rating-head">
        <span class="rating-title"><i class="fa-solid fa-star" style="color:#f59e0b"></i> Rate this <?= e($type) ?></span>
        <span class="rating-avg" data-role="avg">
          <?php if ($s['count'] > 0): ?>
            <b><?= number_format($s['avg'], 1) ?></b> / 5 · <?= number_format($s['count']) ?> vote<?= $s['count'] === 1 ? '' : 's' ?>
          <?php else: ?>
            No ratings yet — be the first
          <?php endif; ?>
        </span>
      </div>
      <div class="stars" data-role="stars">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <button type="button" class="star<?= $mine >= $i ? ' on' : '' ?>" data-v="<?= $i ?>"
                  aria-label="Rate <?= $i ?> out of 5"><i class="fa-solid fa-star"></i></button>
        <?php endfor; ?>
      </div>
      <p class="rating-note" data-role="note"><?= $mine ? 'You rated this ' . $mine . '/5 — click again to change it.' : 'One vote per visitor.' ?></p>
    </div>
    <script>
    (function () {
      var box = document.getElementById('rate-<?= e($uid) ?>');
      if (!box || box.dataset.wired) return;
      box.dataset.wired = '1';
      var stars = box.querySelectorAll('.star');
      var note  = box.querySelector('[data-role=note]');
      var avg   = box.querySelector('[data-role=avg]');

      function paint(n) { stars.forEach(function (s, i) { s.classList.toggle('on', i < n); }); }

      stars.forEach(function (s) {
        s.addEventListener('mouseenter', function () { paint(+s.dataset.v); });
        s.addEventListener('click', function () {
          var v = +s.dataset.v;
          paint(v);
          note.textContent = 'Saving…';
          fetch('<?= e(site_url('rate.php')) ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'type=<?= e($type) ?>&id=<?= (int)$id ?>&rating=' + v
          }).then(function (r) { return r.json(); }).then(function (d) {
            if (d && d.ok) {
              avg.innerHTML = '<b>' + d.avg.toFixed(1) + '</b> / 5 · ' + d.count + ' vote' + (d.count === 1 ? '' : 's');
              note.textContent = 'Thanks! You rated this ' + v + '/5.';
              box.dataset.mine = v;
            } else {
              note.textContent = (d && d.error) || 'Could not save your rating.';
            }
          }).catch(function () { note.textContent = 'Network error — try again.'; });
        });
      });
      box.querySelector('[data-role=stars]').addEventListener('mouseleave', function () {
        paint(+(box.dataset.mine || <?= $mine ?>));
      });
    })();
    </script>
    <?php
}
