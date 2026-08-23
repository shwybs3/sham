<?php
/**
 * Provisions a real, independent subdomain site: creates the subdomain
 * and a fresh MySQL database via cPanel, copies this codebase into the
 * new document root (subdomains on the same cPanel account share this
 * server's filesystem, so this is a plain file copy, not a remote
 * transfer), bootstraps that database with the same schema and seed
 * content this site ships with, and — optionally — a few AI-written
 * articles for the niche you give it.
 *
 * Every step here makes a real, live change on your hosting account.
 * There is no dry-run.
 */
class SiteProvisioner
{
    /** Directories/files never copied into a new subsite. */
    const EXCLUDE = ['.git', 'uploads', 'config.generated.php'];

    public static function provision(string $subLabel, string $niche, int $extraAiArticles = 0): array {
        $log = [];
        $subLabel = strtolower(preg_replace('~[^a-z0-9-]~i', '', $subLabel));
        if ($subLabel === '' || strlen($subLabel) > 60) {
            return ['ok' => false, 'error' => 'Choose a subdomain name using only letters, numbers and hyphens.', 'log' => $log];
        }
        if (!CPanelClient::isConfigured()) {
            return ['ok' => false, 'error' => 'cPanel is not configured in Settings > Subdomains.', 'log' => $log];
        }
        $homeDir = rtrim(trim(setting('cpanel_home_dir')), '/');
        if ($homeDir === '') {
            return ['ok' => false, 'error' => 'Set your cPanel account home directory in Settings > Subdomains first.', 'log' => $log];
        }

        @set_time_limit(180);
        global $pdo;
        $rootDomain = CPanelClient::rootDomain();
        $fullDomain = $subLabel . '.' . $rootDomain;

        $existing = $pdo->prepare("SELECT id FROM subsites WHERE full_domain = ?");
        $existing->execute([$fullDomain]);
        if ($existing->fetchColumn()) {
            return ['ok' => false, 'error' => "$fullDomain has already been provisioned.", 'log' => $log];
        }

        $insert = $pdo->prepare("INSERT INTO subsites (subdomain, root_domain, full_domain, niche, status) VALUES (?,?,?,?, 'provisioning')");
        $insert->execute([$subLabel, $rootDomain, $fullDomain, $niche]);
        $subsiteId = (int)$pdo->lastInsertId();

        $fail = function (string $error) use ($pdo, $subsiteId, &$log) {
            $pdo->prepare("UPDATE subsites SET status='failed', error_log=? WHERE id=?")->execute([$error, $subsiteId]);
            return ['ok' => false, 'error' => $error, 'log' => $log];
        };

        // 1. Create the subdomain on the hosting account.
        $relDir = 'public_html/' . $subLabel;
        $log[] = 'Creating subdomain ' . $fullDomain . ' on cPanel…';
        $r = CPanelClient::createSubdomain($subLabel, $relDir);
        if (!$r['ok']) return $fail('cPanel could not create the subdomain: ' . $r['error']);
        $log[] = 'Subdomain created.';

        // 2. Create a fresh database + user.
        $base = CPanelClient::safeDbBaseName($subLabel);
        $log[] = 'Creating database…';
        $dbRes = CPanelClient::createDatabase($base);
        if (!$dbRes['ok']) return $fail('Could not create database: ' . $dbRes['error']);
        $dbName = self::extractName($dbRes['data'], ['database', 'db', 'name']) ?: (CPanelClient::username() . '_' . $base);

        $dbPass = bin2hex(random_bytes(12));
        $userRes = CPanelClient::createDbUser($base, $dbPass);
        if (!$userRes['ok']) return $fail('Could not create database user: ' . $userRes['error']);
        $dbUser = self::extractName($userRes['data'], ['user', 'name']) ?: (CPanelClient::username() . '_' . $base);

        $privRes = CPanelClient::grantAllPrivileges($dbUser, $dbName);
        if (!$privRes['ok']) return $fail('Could not grant database privileges: ' . $privRes['error']);
        $log[] = 'Database ready.';

        // 3. Copy this codebase into the new document root.
        $docRoot = $homeDir . '/' . $relDir;
        $log[] = 'Copying site files to ' . $docRoot . ' …';
        if (!self::copyTree(ROOT_PATH, $docRoot)) return $fail('Could not copy site files into the new document root. Check filesystem permissions.');
        @mkdir($docRoot . '/uploads', 0755, true);
        @touch($docRoot . '/uploads/.gitkeep');
        $log[] = 'Files copied.';

        // 4. Write the new site's config.
        $siteUrl = 'https://' . $fullDomain;
        $configPhp = "<?php\n"
            . "define('DB_HOST', " . var_export(DB_HOST, true) . ");\n"
            . "define('DB_NAME', " . var_export($dbName, true) . ");\n"
            . "define('DB_USER', " . var_export($dbUser, true) . ");\n"
            . "define('DB_PASS', " . var_export($dbPass, true) . ");\n"
            . "define('SITE_URL', " . var_export($siteUrl, true) . ");\n"
            . "define('APP_SECRET', " . var_export(bin2hex(random_bytes(16)), true) . ");\n";
        if (@file_put_contents($docRoot . '/config.generated.php', $configPhp) === false) {
            return $fail('Could not write the new site\'s config file.');
        }

        // 5. Bootstrap the new database: schema + admin account + seed content.
        try {
            $newPdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . $dbName . ";charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            return $fail('Could not connect to the new database: ' . $e->getMessage());
        }

        sh_ensure_schema($newPdo);
        $log[] = 'Database schema created.';

        $adminUser = 'admin';
        $adminPass = bin2hex(random_bytes(6));
        $newPdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)")
            ->execute([$adminUser, password_hash($adminPass, PASSWORD_DEFAULT)]);

        $niceName = $niche !== '' ? ucfirst($niche) : ucfirst($subLabel);
        $newSettings = [
            'site_name' => $niceName,
            'site_tagline' => 'News, guides and free tools about ' . ($niche ?: $subLabel) . '.',
            'site_description' => 'A focused hub covering ' . ($niche ?: $subLabel) . ': articles, comparisons, tutorials and free web tools.',
            'gsc_site_url' => $siteUrl . '/',
            'gemini_model' => 'gemini-2.0-flash',
            'tip_presets' => '3,5,10',
            'contact_email' => trim(setting('contact_email', 'contact@yassota.com')),
            // New subsites start in maintenance mode with a link back to this
            // (the parent) site, until their owner has real content ready and
            // turns it off in Settings > General.
            'maintenance_mode' => '1',
            'parent_site_url' => rtrim(SITE_URL, '/'),
        ];
        $ins = $newPdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
        foreach ($newSettings as $k => $v) $ins->execute([$k, $v]);
        // Carry over shared provider keys so the new site's AI/ads work immediately without re-entering them.
        foreach (['gemini_api_key', 'openrouter_api_key', 'adsense_publisher_id'] as $carry) {
            $val = trim(setting($carry));
            if ($val !== '') $ins->execute([$carry, $val]);
        }
        $log[] = 'Admin account created.';

        require_once __DIR__ . '/../../seed/seed_categories.php';
        require_once __DIR__ . '/../../seed/seed_articles.php';
        require_once __DIR__ . '/../../seed/seed_tools.php';
        require_once __DIR__ . '/../../seed/seed_pro_tools.php';
        require_once __DIR__ . '/../../seed/seed_products.php';
        seed_categories($newPdo);
        seed_articles($newPdo);
        seed_tools($newPdo);
        seed_pro_tools($newPdo);
        seed_products($newPdo);
        $log[] = 'Seeded 21 starter articles, 40 tools and 10 store products.';

        // 6. Optional: a few extra AI-written articles for this niche.
        $written = 0;
        if ($extraAiArticles > 0 && $niche !== '' && (GeminiClient::isConfigured() || OpenRouterClient::isConfigured())) {
            $extraAiArticles = min(5, $extraAiArticles);
            for ($i = 0; $i < $extraAiArticles; $i++) {
                if (self::generateNicheArticle($newPdo, $niche)) $written++;
            }
            $log[] = "Generated $written AI article(s) about \"$niche\".";
        }

        @touch($docRoot . '/install/install.lock');

        $pdo->prepare("UPDATE subsites SET status='ready', doc_root=?, db_name=?, db_user=?, admin_user=? WHERE id=?")
            ->execute([$docRoot, $dbName, $dbUser, $adminUser, $subsiteId]);

        return [
            'ok' => true,
            'log' => $log,
            'site_url' => $siteUrl,
            'admin_url' => $siteUrl . '/admin/',
            'admin_user' => $adminUser,
            'admin_pass' => $adminPass,
            'articles_written' => $written,
        ];
    }

    private static function extractName($data, array $keys): ?string {
        if (!is_array($data)) return null;
        foreach ($keys as $k) if (!empty($data[$k])) return (string)$data[$k];
        return null;
    }

    private static function copyTree(string $src, string $dst): bool {
        if (!is_dir($src)) return false;
        if (!is_dir($dst) && !@mkdir($dst, 0755, true)) return false;

        $items = @scandir($src);
        if ($items === false) return false;

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            if (in_array($item, self::EXCLUDE, true)) continue;

            $from = $src . '/' . $item;
            $to = $dst . '/' . $item;
            if (is_dir($from)) {
                if (!self::copyTree($from, $to)) return false;
            } else {
                if (!@copy($from, $to)) return false;
            }
        }
        return true;
    }

    private static function generateNicheArticle(PDO $newPdo, string $niche): bool {
        $system = "You are a senior tech editor. Write ONLY valid minified JSON (no markdown fences) with keys: "
            . "title, excerpt (<=160 chars), body_html (well-structured HTML using <h2>/<h3>/<p>/<ul> — 600-900 words), "
            . "meta_title, meta_description (<=160 chars), meta_keywords, tags (comma separated, 4-6 tags).";
        $prompt = "Write an original, useful English-language article for a site about: {$niche}. "
            . "Pick a specific, search-worthy angle within that topic — not a generic overview.";
        $res = AIRouter::generate($prompt, $system);
        if (!$res['ok']) return false;

        $clean = trim(preg_replace('~^```json|```$~m', '', trim($res['text'])));
        $json = json_decode($clean, true);
        if (!$json || empty($json['title']) || empty($json['body_html'])) return false;

        $body = $json['body_html'];
        $slug = slugify($json['title']);
        $fields = [
            'title' => $json['title'], 'slug' => $slug, 'content_type' => 'article',
            'excerpt' => $json['excerpt'] ?? excerpt_from_html($body), 'body' => $body,
            'meta_title' => $json['meta_title'] ?? $json['title'],
            'meta_description' => $json['meta_description'] ?? '',
            'meta_keywords' => $json['meta_keywords'] ?? '',
            'tags' => $json['tags'] ?? '', 'author' => 'Editorial Team', 'status' => 'draft',
            'reading_time' => reading_time_from_html($body), 'published_at' => date('Y-m-d H:i:s'),
        ];
        try {
            $sql = "INSERT INTO articles (" . implode(',', array_keys($fields)) . ") VALUES (" . implode(',', array_map(fn($k) => ":$k", array_keys($fields))) . ")";
            $newPdo->prepare($sql)->execute($fields);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
