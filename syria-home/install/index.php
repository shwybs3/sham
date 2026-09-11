<?php
/**
 * Syria Home — GUI install wizard.
 * Runs once. Writes ../config.generated.php, creates the schema,
 * creates the admin account, and seeds demo content (categories,
 * 20 articles, 20 tools). Locks itself with install.lock when done.
 */
require_once __DIR__ . '/../includes/functions.php'; // safe: no DB calls happen until we open one ourselves
if (session_status() === PHP_SESSION_NONE) session_start();

$lockFile = __DIR__ . '/install.lock';
$installed = file_exists($lockFile);

/** App root without a ".." segment — some hosts restrict such paths via open_basedir. */
function sh_app_root(): string {
    return realpath(dirname(__DIR__)) ?: dirname(__DIR__);
}

/** The optional demo/starter content, in the order it must be applied. */
const SH_SEEDS = [
    'seed_categories.php' => 'seed_categories',
    'seed_articles.php'   => 'seed_articles',
    'seed_tools.php'      => 'seed_tools',
    'seed_pro_tools.php'  => 'seed_pro_tools',
    'seed_products.php'   => 'seed_products',
];

/** Which seed files are actually present and readable on this server. */
function sh_missing_seeds(): array {
    $missing = [];
    foreach (array_keys(SH_SEEDS) as $file) {
        if (!is_readable(sh_app_root() . '/seed/' . $file)) $missing[] = $file;
    }
    return $missing;
}

$step = max(1, min(5, (int)($_GET['step'] ?? 1)));
$data = $_SESSION['install'] ?? [
    'site_name' => 'Yassota',
    'site_tagline' => 'باقات متابعين ومشاهدات وإعجابات حقيقية لإنستقرام وفيسبوك ويوتيوب وتيليجرام، وأدوات حماية رقمية — بدفع فوري بالعملات الرقمية.',
    'site_description' => 'باقات متابعين ومشاهدات وإعجابات حقيقية لإنستقرام وفيسبوك ويوتيوب وتيليجرام، بالإضافة إلى أدوات حماية رقمية، بالدفع الفوري بالعملات الرقمية عبر NOWPayments.',
    'db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'admin_user' => 'admin', 'admin_pass' => '', 'admin_pass2' => '',
];
$errors = [];

function render_head(string $title): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?> — Yassota Setup</title>
<style>
:root{--brand1:#22d3ee;--brand2:#a855f7;--ink:#0f172a;--muted:#64748b;--bg:#f4f6fb;--card:#fff;--ok:#16a34a;--err:#dc2626;--warn:#b45309}
*{box-sizing:border-box}body{margin:0;font-family:'Segoe UI',Roboto,Arial,sans-serif;background:linear-gradient(160deg,#eef2ff,var(--bg) 40%);color:var(--ink);min-height:100vh}
.wrap{max-width:640px;margin:0 auto;padding:48px 20px}
.brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:22px;margin-bottom:28px}
.brand span.dot{width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,var(--brand1),var(--brand2));display:inline-flex;align-items:center;justify-content:center;color:#fff}
.steps{display:flex;gap:6px;margin-bottom:24px}
.steps i{flex:1;height:6px;border-radius:4px;background:#e2e8f0}
.steps i.on{background:linear-gradient(90deg,var(--brand1),var(--brand2))}
.card{background:var(--card);border-radius:18px;padding:32px;box-shadow:0 10px 40px rgba(15,23,42,.08)}
h1{font-size:22px;margin:0 0 6px}p.sub{color:var(--muted);margin:0 0 22px;font-size:14px;line-height:1.6}
label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px;color:#334155}
input,textarea{width:100%;padding:11px 13px;border:1px solid #dde3ee;border-radius:10px;font-size:14px;font-family:inherit}
input:focus,textarea:focus{outline:2px solid var(--brand1);border-color:transparent}
textarea{resize:vertical;min-height:70px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.btn{display:inline-flex;align-items:center;gap:8px;justify-content:center;background:linear-gradient(135deg,var(--brand1),var(--brand2));color:#fff;border:0;padding:13px 22px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;margin-top:22px}
.btn.secondary{background:#eef1f8;color:#334155}
.actions{display:flex;justify-content:space-between;align-items:center}
.err{background:#fef2f2;border:1px solid #fecaca;color:var(--err);padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:16px}
.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok);padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:16px}
.warn{background:#fffbeb;border:1px solid #fde68a;color:var(--warn);padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;line-height:1.65}
.warn code{background:#fef3c7;padding:1px 5px;border-radius:4px}
ul.reqs li .warnmark{color:var(--warn);font-weight:700}
ul.reqs{list-style:none;padding:0;margin:0}
ul.reqs li{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f1f5f9;font-size:14px}
ul.reqs li .yes{color:var(--ok);font-weight:700}ul.reqs li .no{color:var(--err);font-weight:700}
.hint{color:var(--muted);font-size:12px;margin-top:4px}
.spinner-list{font-size:13px;color:#334155;line-height:2}
.spinner-list .go{color:var(--ok)}
</style></head><body><div class="wrap">
<div class="brand"><span class="dot">Y</span> Yassota Setup</div>
<?php }

function render_foot(): void { ?>
</div></body></html>
<?php }

/* ── already installed ── */
if ($installed && $step < 5) {
    render_head('Already installed');
    echo '<div class="card"><h1>Already installed ✅</h1><p class="sub">This site is already set up. Delete <code>install/install.lock</code> manually if you really need to re-run this wizard (this will NOT be done automatically for safety). To load starter packages without reinstalling, use <b>Admin → Store Products → Seed default packages</b>.</p>
    <a class="btn" href="../">Go to the homepage</a> <a class="btn secondary" href="../admin/">Open admin panel</a></div>';
    render_foot();
    exit;
}

/* ── handle POSTs ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedStep = (int)($_POST['step'] ?? 1);

    if ($postedStep === 2) {
        $data['site_name'] = trim($_POST['site_name'] ?? '') ?: 'Syria Home';
        $data['site_tagline'] = trim($_POST['site_tagline'] ?? '');
        $data['site_description'] = trim($_POST['site_description'] ?? '');
        $_SESSION['install'] = $data;
        header('Location: ?step=3'); exit;
    }

    if ($postedStep === 3) {
        $data['db_host'] = trim($_POST['db_host'] ?? 'localhost');
        $data['db_name'] = trim($_POST['db_name'] ?? '');
        $data['db_user'] = trim($_POST['db_user'] ?? '');
        $data['db_pass'] = (string)($_POST['db_pass'] ?? '');
        $_SESSION['install'] = $data;
        try {
            $testPdo = new PDO("mysql:host={$data['db_host']};dbname={$data['db_name']};charset=utf8mb4",
                $data['db_user'], $data['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            header('Location: ?step=4'); exit;
        } catch (PDOException $e) {
            $errors[] = 'Could not connect: ' . $e->getMessage();
            $step = 3;
        }
    }

    if ($postedStep === 4) {
        $data['admin_user'] = trim($_POST['admin_user'] ?? 'admin');
        $data['admin_pass'] = (string)($_POST['admin_pass'] ?? '');
        $data['admin_pass2'] = (string)($_POST['admin_pass2'] ?? '');
        $_SESSION['install'] = $data;
        if (strlen($data['admin_user']) < 3) $errors[] = 'Choose an admin username of at least 3 characters.';
        if (strlen($data['admin_pass']) < 8) $errors[] = 'The admin password must be at least 8 characters.';
        if ($data['admin_pass'] !== $data['admin_pass2']) $errors[] = 'Passwords do not match.';
        if (!$errors) { header('Location: ?step=5'); exit; }
        $step = 4;
    }

    if ($postedStep === 5) {
        // ── Run the actual install ──
        try {
            $pdo = new PDO("mysql:host={$data['db_host']};dbname={$data['db_name']};charset=utf8mb4",
                $data['db_user'], $data['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

            require_once __DIR__ . '/../includes/schema.php';
            // Wipe any tables left behind by an earlier failed/partial install attempt
            // against this same database before creating a guaranteed-clean schema.
            // Safe here specifically: install.lock doesn't exist yet, so there is no
            // real site data this could ever destroy.
            sh_reset_schema($pdo);
            sh_ensure_schema($pdo);

            /* request_is_https() rather than isset($_SERVER['HTTPS']) — the latter
               is true even when the value is the string "off", which bakes an
               http:// canonical into every URL the site will ever publish. */
            $siteUrl = (request_is_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']
                . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');

            $configPhp = "<?php\n"
                . "define('DB_HOST', " . var_export($data['db_host'], true) . ");\n"
                . "define('DB_NAME', " . var_export($data['db_name'], true) . ");\n"
                . "define('DB_USER', " . var_export($data['db_user'], true) . ");\n"
                . "define('DB_PASS', " . var_export($data['db_pass'], true) . ");\n"
                . "define('SITE_URL', " . var_export($siteUrl, true) . ");\n"
                . "define('APP_SECRET', " . var_export(bin2hex(random_bytes(16)), true) . ");\n";
            file_put_contents(__DIR__ . '/../config.generated.php', $configPhp);

            // Admin account
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");
            $stmt->execute([$data['admin_user'], password_hash($data['admin_pass'], PASSWORD_DEFAULT)]);

            // Settings
            $settings = [
                'site_name' => $data['site_name'],
                'site_tagline' => $data['site_tagline'],
                'site_description' => $data['site_description'],
                'adsense_publisher_id' => '',
                'google_client_id' => '',
                'google_client_secret' => '',
                'google_ads_developer_token' => '',
                'google_ads_customer_id' => '',
                'ga4_property_id' => '',
                'gsc_site_url' => $siteUrl . '/',
                'gemini_api_key' => '',
                'gemini_model' => 'gemini-2.0-flash',
                'openrouter_api_key' => '',
                'nowpayments_api_key' => '',
                'nowpayments_ipn_secret' => '',
                'tip_presets' => '3,5,10',
                'cpanel_host' => '',
                'cpanel_username' => '',
                'cpanel_api_token' => '',
                'cpanel_root_domain' => '',
                'cpanel_home_dir' => '',
                'contact_email' => 'contact@yassota.com',
                'support_telegram' => '',
                'maintenance_mode' => '0',
                'parent_site_url' => '',
                'social_twitter' => '', 'social_facebook' => '', 'social_linkedin' => '',
            ];
            $ins = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
            foreach ($settings as $k => $v) $ins->execute([$k, $v]);

            /* Starter content is optional: a partial upload (a missing seed/
               folder) or one bad seed must never abandon an install that has
               already created the schema, the admin account and the config. */
            $seedSkipped = [];
            foreach (SH_SEEDS as $file => $fn) {
                $path = sh_app_root() . '/seed/' . $file;
                if (!is_readable($path)) { $seedSkipped[$file] = 'not uploaded to the server'; continue; }
                try {
                    require_once $path;
                    if (!function_exists($fn)) { $seedSkipped[$file] = 'file present but incomplete'; continue; }
                    $fn($pdo);
                } catch (Throwable $seedError) {
                    $seedSkipped[$file] = $seedError->getMessage();
                }
            }

            file_put_contents($lockFile, date('c'));
            unset($_SESSION['install']);

            render_head('Done');
            echo '<div class="card"><h1>🎉 ' . htmlspecialchars($data['site_name']) . ' is ready</h1>
            <p class="sub">The database, your admin account and the site settings are all set up. The wizard is now locked and won\'t run again.</p>
            <div class="ok">Admin account: <b>' . htmlspecialchars($data['admin_user']) . '</b> — keep your password safe.</div>';
            if ($seedSkipped) {
                echo '<div class="warn"><b>Starter content was skipped</b> for ' . count($seedSkipped) . ' file(s) — your site works, but it starts empty:<ul style="margin:8px 0 0;padding-inline-start:18px">';
                foreach ($seedSkipped as $file => $why) {
                    echo '<li><code>seed/' . htmlspecialchars($file) . '</code> — ' . htmlspecialchars($why) . '</li>';
                }
                echo '</ul><p style="margin:10px 0 0">Upload the missing files into the <code>seed/</code> folder, then use
                <b>Admin → Store Products → Seed default packages</b> to load the packages. Articles and tools can be added
                from the admin panel at any time.</p></div>';
            }
            echo '<a class="btn" href="../">View the homepage</a> <a class="btn secondary" href="../admin/">Open admin panel</a></div>';
            render_foot();
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Install failed: ' . $e->getMessage();
            $step = 4;
        }
    }
}

$_SESSION['install'] = $data;

/* ── render current step ── */
render_head('Step ' . $step);
?>
<div class="steps">
  <?php for ($i = 1; $i <= 4; $i++): ?><i class="<?= $i <= $step ? 'on' : '' ?>"></i><?php endfor; ?>
</div>
<div class="card">

<?php if ($errors): foreach ($errors as $err): ?>
  <div class="err"><?= e($err) ?></div>
<?php endforeach; endif; ?>

<?php if ($step === 1): ?>
  <h1>Welcome</h1>
  <p class="sub">This wizard sets up your store: database, admin account, and the starter packages, articles and free tools. It takes about two minutes.</p>
  <ul class="reqs">
    <?php
    $reqs = [
      'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
      'PDO MySQL extension' => extension_loaded('pdo_mysql'),
      'cURL extension' => extension_loaded('curl'),
      'uploads/ writable' => is_writable(__DIR__ . '/../uploads'),
      'config file writable' => is_writable(__DIR__ . '/..'),
    ];
    foreach ($reqs as $label => $ok): ?>
      <li><span><?= e($label) ?></span><span class="<?= $ok ? 'yes' : 'no' ?>"><?= $ok ? '✓ OK' : '✗ Missing' ?></span></li>
    <?php endforeach; ?>
    <?php $missingSeeds = sh_missing_seeds(); ?>
    <li><span>Starter content (<code>seed/</code>)</span>
      <span class="<?= $missingSeeds ? 'warnmark' : 'yes' ?>"><?= $missingSeeds ? '⚠ ' . count($missingSeeds) . ' file(s) missing' : '✓ All ' . count(SH_SEEDS) . ' present' ?></span></li>
  </ul>
  <?php if ($missingSeeds): ?>
    <div class="warn" style="margin-top:16px"><b>Optional — the install will still work.</b> These files aren't on the server, so that part of the starter content will be skipped:
      <ul style="margin:8px 0 0;padding-inline-start:18px"><?php foreach ($missingSeeds as $f): ?><li><code>seed/<?= e($f) ?></code></li><?php endforeach; ?></ul>
      <p style="margin:10px 0 0">Upload the whole <code>seed/</code> folder over FTP and reload this page to fix it — or continue now and load the packages later from <b>Admin → Store Products</b>.</p>
    </div>
  <?php endif; ?>
  <div class="actions"><span></span><a class="btn" href="?step=2">Start setup →</a></div>

<?php elseif ($step === 2): ?>
  <h1>Your site</h1>
  <p class="sub">Name and describe the site. You can change this later in Settings.</p>
  <form method="post">
    <input type="hidden" name="step" value="2">
    <label>Site name</label>
    <input name="site_name" value="<?= e($data['site_name']) ?>" required>
    <label>Tagline (short, shown in the header)</label>
    <input name="site_tagline" value="<?= e($data['site_tagline']) ?>">
    <label>Description (used for SEO defaults)</label>
    <textarea name="site_description"><?= e($data['site_description']) ?></textarea>
    <div class="actions"><a class="btn secondary" href="?step=1">← Back</a><button class="btn" type="submit">Continue →</button></div>
  </form>

<?php elseif ($step === 3): ?>
  <h1>Database</h1>
  <p class="sub">From your hosting cPanel: create a MySQL database + user with all privileges, then enter the details below. Tables are created automatically — no SQL import needed.</p>
  <form method="post">
    <input type="hidden" name="step" value="3">
    <div class="row2">
      <div><label>DB Host</label><input name="db_host" value="<?= e($data['db_host']) ?>" required></div>
      <div><label>DB Name</label><input name="db_name" value="<?= e($data['db_name']) ?>" required></div>
    </div>
    <div class="row2">
      <div><label>DB User</label><input name="db_user" value="<?= e($data['db_user']) ?>" required></div>
      <div><label>DB Password</label><input type="password" name="db_pass" value="<?= e($data['db_pass']) ?>"></div>
    </div>
    <div class="actions"><a class="btn secondary" href="?step=2">← Back</a><button class="btn" type="submit">Test &amp; continue →</button></div>
  </form>

<?php elseif ($step === 4): ?>
  <h1>Admin account</h1>
  <p class="sub">This logs you into the admin panel at <code>/admin/</code>.</p>
  <form method="post">
    <input type="hidden" name="step" value="4">
    <label>Username</label>
    <input name="admin_user" value="<?= e($data['admin_user']) ?>" required>
    <label>Password (min 8 characters)</label>
    <input type="password" name="admin_pass" required>
    <label>Confirm password</label>
    <input type="password" name="admin_pass2" required>
    <div class="actions"><a class="btn secondary" href="?step=3">← Back</a><button class="btn" type="submit">Review →</button></div>
  </form>

<?php elseif ($step === 5): ?>
  <h1>Ready to install</h1>
  <p class="sub">This will create the database tables, your admin account, and load whatever starter content is available on the server.</p>
  <?php $missingSeeds = sh_missing_seeds(); ?>
  <div class="spinner-list">
    <div>✓ Site: <b><?= e($data['site_name']) ?></b></div>
    <div>✓ Database: <b><?= e($data['db_name']) ?></b> on <?= e($data['db_host']) ?></div>
    <div>✓ Admin user: <b><?= e($data['admin_user']) ?></b></div>
    <div><?= $missingSeeds ? '⚠' : '✓' ?> Starter content: <b><?= count(SH_SEEDS) - count($missingSeeds) ?>/<?= count(SH_SEEDS) ?></b> seed files found<?= $missingSeeds ? ' — the rest is skipped' : '' ?></div>
  </div>
  <form method="post">
    <input type="hidden" name="step" value="5">
    <div class="actions"><a class="btn secondary" href="?step=4">← Back</a><button class="btn" type="submit">Install now 🚀</button></div>
  </form>
<?php endif; ?>

</div>
<?php render_foot(); ?>
