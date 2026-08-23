<?php
$tab = $_GET['tab'] ?? 'general';
$tabs = ['general' => 'General', 'themes' => 'Themes', 'api' => 'API Keys', 'payments' => 'Payments', 'subdomains' => 'Subdomains', 'ads' => 'Advertisements', 'seo' => 'SEO', 'sitemap' => 'Sitemap & Robots', 'security' => 'Security', 'social' => 'Social Media'];
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $formTab = $_POST['tab'] ?? 'general';

    if ($formTab === 'general') {
        set_setting('site_name', trim($_POST['site_name'] ?? 'Syria Home'));
        set_setting('site_tagline', trim($_POST['site_tagline'] ?? ''));
        set_setting('site_description', trim($_POST['site_description'] ?? ''));
        set_setting('contact_email', trim($_POST['contact_email'] ?? ''));
        set_setting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        set_setting('parent_site_url', trim($_POST['parent_site_url'] ?? ''));
        set_setting('logo_url', trim($_POST['logo_url'] ?? ''));
        set_setting('logo_dark_url', trim($_POST['logo_dark_url'] ?? ''));
        $msg = ['ok', 'General settings saved.'];
    }

    if ($formTab === 'themes') {
        set_setting('site_theme', trim($_POST['site_theme'] ?? 'default'));
        $msg = ['ok', 'Theme saved.'];
    }

    if ($formTab === 'api') {
        foreach (['google_client_id','google_client_secret','google_ads_developer_token','google_ads_customer_id','ga4_property_id','gsc_site_url','gemini_api_key','gemini_model','openrouter_api_key'] as $k) {
            set_setting($k, trim($_POST[$k] ?? ''));
        }
        $msg = ['ok', 'API keys saved.'];
    }

    if ($formTab === 'test_nowpayments') {
        $test = NOWPayments::testConnection();
        $msg = [$test['ok'] ? 'ok' : 'err', $test['ok'] ? $test['message'] : $test['error']];
    }

    if ($formTab === 'payments') {
        set_setting('nowpayments_api_key', trim($_POST['nowpayments_api_key'] ?? ''));
        set_setting('nowpayments_ipn_secret', trim($_POST['nowpayments_ipn_secret'] ?? ''));
        $presets = array_filter(array_map('trim', explode(',', $_POST['tip_presets'] ?? '')), 'is_numeric');
        set_setting('tip_presets', $presets ? implode(',', $presets) : '3,5,10');
        set_setting('paypal_email', trim($_POST['paypal_email'] ?? ''));
        set_setting('paypal_client_id', trim($_POST['paypal_client_id'] ?? ''));
        set_setting('paypal_mode', trim($_POST['paypal_mode'] ?? 'sandbox'));
        set_setting('usdt_wallet', trim($_POST['usdt_wallet'] ?? ''));
        set_setting('usdt_network', trim($_POST['usdt_network'] ?? 'TRC20'));
        set_setting('promote_price', trim($_POST['promote_price'] ?? '49'));
        set_setting('feature_price', trim($_POST['feature_price'] ?? '99'));
        set_setting('store_currency', trim($_POST['store_currency'] ?? 'USD'));
        $msg = ['ok', 'Payment settings saved.'];
    }

    if ($formTab === 'subdomains') {
        foreach (['cpanel_host','cpanel_username','cpanel_api_token','cpanel_root_domain','cpanel_home_dir'] as $k) {
            set_setting($k, trim($_POST[$k] ?? ''));
        }
        $msg = ['ok', 'cPanel settings saved.'];
    }

    if ($formTab === 'ads') {
        set_setting('adsense_publisher_id', trim($_POST['adsense_publisher_id'] ?? ''));
        foreach (['home_top','home_mid','article_top','article_bottom','tool_top','tool_bottom'] as $slot) {
            set_setting('adsense_slot_' . $slot, trim($_POST['slot_' . $slot] ?? ''));
        }
        $msg = ['ok', 'Advertisement settings saved.'];
    }

    if ($formTab === 'seo') {
        set_setting('seo_default_keywords', trim($_POST['seo_default_keywords'] ?? ''));
        set_setting('google_site_verification', trim($_POST['google_site_verification'] ?? ''));
        set_setting('bing_site_verification', trim($_POST['bing_site_verification'] ?? ''));
        set_setting('indexnow_key', trim($_POST['indexnow_key'] ?? ''));
        $msg = ['ok', 'SEO settings saved.'];
    }

    if ($formTab === 'sitemap') {
        $sitemapAction = $_POST['sitemap_action'] ?? '';
        if ($sitemapAction === 'generate_sitemap') {
            ob_start();
            include ROOT_PATH . '/sitemap.php';
            $xml = ob_get_clean();
            file_put_contents(ROOT_PATH . '/sitemap.xml', $xml);
            $msg = ['ok', 'sitemap.xml generated (' . strlen($xml) . ' bytes).'];
        } elseif ($sitemapAction === 'generate_robots') {
            $robots = "User-agent: *\nAllow: /\nSitemap: " . rtrim(SITE_URL, '/') . "/sitemap.xml\n";
            file_put_contents(ROOT_PATH . '/robots.txt', $robots);
            $msg = ['ok', 'robots.txt generated.'];
        }
    }

    if ($formTab === 'social') {
        foreach (['social_twitter','social_facebook','social_linkedin','social_youtube','social_github','social_instagram'] as $k) set_setting($k, trim($_POST[$k] ?? ''));
        $msg = ['ok', 'Social links saved.'];
    }

    if ($formTab === 'security') {
        $cur = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        if (!$admin || !password_verify($cur, $admin['password_hash'])) {
            $msg = ['err', 'Current password is incorrect.'];
        } elseif (strlen($new) < 8) {
            $msg = ['err', 'New password must be at least 8 characters.'];
        } else {
            $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
            $msg = ['ok', 'Password updated.'];
        }
    }
}

if (isset($_GET['disconnect_google']) && csrf_check_get()) { GoogleOAuth::disconnect(); header('Location: ?page=settings&tab=api'); exit; }
if (isset($_GET['connected'])) $msg = ['ok', 'Google account connected successfully.'];
if (isset($_GET['google_error'])) $msg = ['err', 'Google connection failed: ' . e($_GET['google_error'])];
?>
<div class="tabs">
  <?php foreach ($tabs as $k => $label): ?>
    <a class="<?= $tab === $k ? 'active' : '' ?>" href="?page=settings&tab=<?= $k ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if ($msg): flash($msg[0], $msg[1]); endif; ?>

<div class="card">
<?php if ($tab === 'general'): ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="general">
    <label>Website name</label><input type="text" name="site_name" value="<?= e(setting('site_name')) ?>">
    <label>Tagline</label><input type="text" name="site_tagline" value="<?= e(setting('site_tagline')) ?>">
    <label>Description (SEO default)</label><textarea name="site_description"><?= e(setting('site_description')) ?></textarea>
    <label>Contact email (shown on the Contact page)</label><input type="text" name="contact_email" value="<?= e(setting('contact_email', 'contact@yassota.com')) ?>">

    <h3>Logo</h3>
    <div class="row2">
      <div><label>Logo URL (light mode)</label><input type="text" name="logo_url" value="<?= e(setting('logo_url')) ?>" placeholder="https://... or /uploads/logo.png"><p class="hint">Leave blank to use the text logo. Shows as an &lt;img&gt; in the header.</p></div>
      <div><label>Logo URL (dark mode / optional)</label><input type="text" name="logo_dark_url" value="<?= e(setting('logo_dark_url')) ?>" placeholder="https://... dark version"></div>
    </div>

    <h3>Maintenance mode</h3>
    <label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="maintenance_mode" style="width:auto" <?= (int)setting('maintenance_mode', 0) ? 'checked' : '' ?>> Show a "Coming soon" page to visitors instead of the live site</label>
    <p class="hint">You (logged in as admin) can still browse the live site normally. Sitemap, robots.txt and the payment webhook stay reachable regardless.</p>
    <label>Link back to (optional — shown as a "visit our other site" button)</label>
    <input type="text" name="parent_site_url" value="<?= e(setting('parent_site_url')) ?>" placeholder="https://syria-home.yassota.com">

    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
  </form>

<?php elseif ($tab === 'themes'): ?>
  <?php
  $themes = [
    'default'  => ['label' => 'Default Purple',  'brand1' => '#6366f1', 'brand2' => '#22d3ee'],
    'dark'     => ['label' => 'Dark Premium',     'brand1' => '#a78bfa', 'brand2' => '#34d399'],
    'ocean'    => ['label' => 'Ocean Blue',       'brand1' => '#0ea5e9', 'brand2' => '#38bdf8'],
    'sunset'   => ['label' => 'Sunset Orange',    'brand1' => '#f97316', 'brand2' => '#fbbf24'],
  ];
  $currentTheme = setting('site_theme', 'default');
  ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="themes">
    <h3 style="margin-top:0">Choose site color theme</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px">
      <?php foreach ($themes as $key => $t): ?>
      <label style="cursor:pointer;border:2px solid <?= $currentTheme === $key ? 'var(--brand1)' : 'var(--line)' ?>;border-radius:14px;padding:16px;display:flex;align-items:center;gap:12px">
        <input type="radio" name="site_theme" value="<?= $key ?>" style="width:auto" <?= $currentTheme === $key ? 'checked' : '' ?>>
        <div>
          <div style="display:flex;gap:6px;margin-bottom:6px">
            <span style="width:22px;height:22px;border-radius:50%;background:<?= $t['brand1'] ?>"></span>
            <span style="width:22px;height:22px;border-radius:50%;background:<?= $t['brand2'] ?>"></span>
          </div>
          <strong style="font-size:13px"><?= $t['label'] ?></strong>
        </div>
      </label>
      <?php endforeach; ?>
    </div>
    <button class="btn" type="submit"><i class="fa-solid fa-palette"></i> Apply theme</button>
  </form>

<?php elseif ($tab === 'api'): ?>
  <h3 style="margin-top:0"><i class="fa-brands fa-google"></i> Google APIs (AdSense · Search Console · Analytics · Ads)</h3>
  <p class="hint">Create an OAuth Client ID (Web application) in Google Cloud Console → APIs &amp; Services → Credentials, and add this exact redirect URI:</p>
  <p><code><?= e(site_url('admin/google-callback.php')) ?></code></p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="api">
    <div class="row2">
      <div><label>Google OAuth Client ID</label><input type="text" name="google_client_id" value="<?= e(setting('google_client_id')) ?>" placeholder="xxxx.apps.googleusercontent.com"></div>
      <div><label>Google OAuth Client Secret</label><input type="password" name="google_client_secret" value="<?= e(setting('google_client_secret')) ?>"></div>
    </div>
    <div class="row2">
      <div><label>Google Ads developer token</label><input type="text" name="google_ads_developer_token" value="<?= e(setting('google_ads_developer_token')) ?>"><p class="hint">From your Google Ads Manager account → API Center.</p></div>
      <div><label>Google Ads Customer ID</label><input type="text" name="google_ads_customer_id" value="<?= e(setting('google_ads_customer_id')) ?>" placeholder="123-456-7890"></div>
    </div>
    <div class="row2">
      <div><label>GA4 Property ID</label><input type="text" name="ga4_property_id" value="<?= e(setting('ga4_property_id')) ?>" placeholder="e.g. 123456789"></div>
      <div><label>Search Console site URL</label><input type="text" name="gsc_site_url" value="<?= e(setting('gsc_site_url')) ?>"></div>
    </div>
    <button class="btn" style="margin-top:10px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Google settings</button>
  </form>

  <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line)">
    <?php if (GoogleOAuth::isConnected()): ?>
      <span class="badge ok"><i class="fa-solid fa-check"></i> Google account connected</span>
      <a class="btn red sm" style="margin-left:8px" href="?page=settings&tab=api&disconnect_google=1&csrf=<?= csrf_token() ?>">Disconnect</a>
    <?php elseif (GoogleOAuth::isConfigured()): ?>
      <?php if (!preg_match('~^\d+-[a-z0-9]+\.apps\.googleusercontent\.com$~i', GoogleOAuth::clientId())): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:10px;font-size:13.5px;margin-bottom:10px">
          <i class="fa-solid fa-triangle-exclamation"></i> This doesn't look like a real Google OAuth Client ID — it should end in <code>.apps.googleusercontent.com</code>.
          Clicking Connect with this value will fail on Google's side with <code>invalid_client</code>. Copy the Client ID again from
          <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console → Credentials</a> — make sure it's the <b>Client ID</b>, not the Secret, and that there's no extra whitespace.
        </div>
      <?php endif; ?>
      <a class="btn" href="<?= e(GoogleOAuth::authorizeUrl()) ?>"><i class="fa-brands fa-google"></i> Connect Google account</a>
    <?php else: ?>
      <span class="badge off">Add a Client ID + Secret above, then save, to enable the Connect button.</span>
    <?php endif; ?>
  </div>

  <h3 style="margin-top:32px"><i class="fa-solid fa-robot"></i> AI Assistant</h3>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="api">
    <input type="hidden" name="google_client_id" value="<?= e(setting('google_client_id')) ?>">
    <input type="hidden" name="google_client_secret" value="<?= e(setting('google_client_secret')) ?>">
    <input type="hidden" name="google_ads_developer_token" value="<?= e(setting('google_ads_developer_token')) ?>">
    <input type="hidden" name="google_ads_customer_id" value="<?= e(setting('google_ads_customer_id')) ?>">
    <input type="hidden" name="ga4_property_id" value="<?= e(setting('ga4_property_id')) ?>">
    <input type="hidden" name="gsc_site_url" value="<?= e(setting('gsc_site_url')) ?>">
    <div class="row2">
      <div><label>Gemini API key</label><input type="password" name="gemini_api_key" value="<?= e(setting('gemini_api_key')) ?>" placeholder="AIza..."><p class="hint">From <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">Google AI Studio</a>.</p></div>
      <div><label>Gemini model</label><input type="text" name="gemini_model" value="<?= e(setting('gemini_model', 'gemini-2.0-flash')) ?>"></div>
    </div>
    <label>OpenRouter API key (free fallback if Gemini is unset/unavailable)</label>
    <input type="password" name="openrouter_api_key" value="<?= e(setting('openrouter_api_key')) ?>" placeholder="sk-or-...">
    <button class="btn" style="margin-top:10px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save AI settings</button>
  </form>

<?php elseif ($tab === 'payments'): ?>
  <h3 style="margin-top:0"><i class="fa-solid fa-wallet"></i> NOWPayments (crypto)</h3>
  <p class="hint">Create a free account at <a href="https://nowpayments.io" target="_blank" rel="noopener">nowpayments.io</a>, grab your API key from the dashboard, and paste this exact IPN callback URL into your NOWPayments account settings so payments get confirmed automatically:</p>
  <p><code><?= e(NOWPayments::webhookUrl()) ?></code></p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="payments">
    <div class="row2">
      <div><label>NOWPayments API key</label><input type="password" name="nowpayments_api_key" value="<?= e(setting('nowpayments_api_key')) ?>"></div>
      <div><label>NOWPayments IPN secret</label><input type="password" name="nowpayments_ipn_secret" value="<?= e(setting('nowpayments_ipn_secret')) ?>">
        <p class="hint">Found under Settings &gt; IPN in your NOWPayments dashboard. Required — payments can't be confirmed without it.</p>
      </div>
    </div>
    <label>Tip amount presets (USD, comma separated)</label>
    <input type="text" name="tip_presets" value="<?= e(setting('tip_presets', '3,5,10')) ?>" style="max-width:220px">
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
  </form>

  <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line)">
    <?php if (NOWPayments::isConfigured()): ?>
      <span class="badge ok"><i class="fa-solid fa-check"></i> Crypto payments active — Store products, tips and premium unlocks now show a real "Pay with crypto" button.</span>
    <?php else: ?>
      <span class="badge off">Add your API key above to enable real crypto checkout everywhere on the site.</span>
    <?php endif; ?>
    <?php if (NOWPayments::isConfigured()): ?>
    <form method="post" style="display:inline;margin-left:10px">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="test_nowpayments">
      <button class="btn gray sm" type="submit"><i class="fa-solid fa-plug-circle-check"></i> Test API key now</button>
    </form>
    <?php endif; ?>
  </div>

  <h3 style="margin-top:28px"><i class="fa-brands fa-paypal"></i> PayPal</h3>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="payments">
    <input type="hidden" name="nowpayments_api_key" value="<?= e(setting('nowpayments_api_key')) ?>">
    <input type="hidden" name="nowpayments_ipn_secret" value="<?= e(setting('nowpayments_ipn_secret')) ?>">
    <input type="hidden" name="tip_presets" value="<?= e(setting('tip_presets', '3,5,10')) ?>">
    <input type="hidden" name="usdt_wallet" value="<?= e(setting('usdt_wallet')) ?>">
    <input type="hidden" name="usdt_network" value="<?= e(setting('usdt_network', 'TRC20')) ?>">
    <input type="hidden" name="promote_price" value="<?= e(setting('promote_price', '49')) ?>">
    <input type="hidden" name="feature_price" value="<?= e(setting('feature_price', '99')) ?>">
    <input type="hidden" name="store_currency" value="<?= e(setting('store_currency', 'USD')) ?>">
    <div class="row2">
      <div><label>PayPal email</label><input type="text" name="paypal_email" value="<?= e(setting('paypal_email')) ?>" placeholder="you@paypal.com"></div>
      <div><label>Mode</label>
        <select name="paypal_mode" style="width:auto">
          <option value="sandbox" <?= setting('paypal_mode','sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (test)</option>
          <option value="live" <?= setting('paypal_mode','sandbox') === 'live' ? 'selected' : '' ?>>Live</option>
        </select>
      </div>
    </div>
    <label>PayPal Client ID (for JS SDK)</label>
    <input type="text" name="paypal_client_id" value="<?= e(setting('paypal_client_id')) ?>">
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save PayPal</button>
  </form>

  <h3 style="margin-top:28px"><i class="fa-solid fa-coins"></i> USDT Direct Transfer</h3>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="payments">
    <input type="hidden" name="nowpayments_api_key" value="<?= e(setting('nowpayments_api_key')) ?>">
    <input type="hidden" name="nowpayments_ipn_secret" value="<?= e(setting('nowpayments_ipn_secret')) ?>">
    <input type="hidden" name="tip_presets" value="<?= e(setting('tip_presets', '3,5,10')) ?>">
    <input type="hidden" name="paypal_email" value="<?= e(setting('paypal_email')) ?>">
    <input type="hidden" name="paypal_client_id" value="<?= e(setting('paypal_client_id')) ?>">
    <input type="hidden" name="paypal_mode" value="<?= e(setting('paypal_mode', 'sandbox')) ?>">
    <input type="hidden" name="promote_price" value="<?= e(setting('promote_price', '49')) ?>">
    <input type="hidden" name="feature_price" value="<?= e(setting('feature_price', '99')) ?>">
    <input type="hidden" name="store_currency" value="<?= e(setting('store_currency', 'USD')) ?>">
    <div class="row2">
      <div><label>USDT Wallet Address</label><input type="text" name="usdt_wallet" value="<?= e(setting('usdt_wallet')) ?>" placeholder="T... (TRC20) or 0x... (ERC20)"></div>
      <div><label>Network</label>
        <select name="usdt_network" style="width:auto">
          <?php foreach (['TRC20','ERC20','BEP20','Polygon'] as $net): ?>
            <option <?= setting('usdt_network','TRC20') === $net ? 'selected' : '' ?>><?= $net ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save USDT</button>
  </form>

  <h3 style="margin-top:28px"><i class="fa-solid fa-tag"></i> Advertising Pricing</h3>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="payments">
    <input type="hidden" name="nowpayments_api_key" value="<?= e(setting('nowpayments_api_key')) ?>">
    <input type="hidden" name="nowpayments_ipn_secret" value="<?= e(setting('nowpayments_ipn_secret')) ?>">
    <input type="hidden" name="tip_presets" value="<?= e(setting('tip_presets', '3,5,10')) ?>">
    <input type="hidden" name="paypal_email" value="<?= e(setting('paypal_email')) ?>">
    <input type="hidden" name="paypal_client_id" value="<?= e(setting('paypal_client_id')) ?>">
    <input type="hidden" name="paypal_mode" value="<?= e(setting('paypal_mode', 'sandbox')) ?>">
    <input type="hidden" name="usdt_wallet" value="<?= e(setting('usdt_wallet')) ?>">
    <input type="hidden" name="usdt_network" value="<?= e(setting('usdt_network', 'TRC20')) ?>">
    <div class="row2">
      <div><label>Promote Price (Sponsored label)</label><input type="number" name="promote_price" value="<?= e(setting('promote_price', '49')) ?>" min="0" step="1" style="max-width:140px"></div>
      <div><label>Feature Price (Homepage spotlight)</label><input type="number" name="feature_price" value="<?= e(setting('feature_price', '99')) ?>" min="0" step="1" style="max-width:140px"></div>
    </div>
    <label>Store currency</label>
    <select name="store_currency" style="width:auto">
      <?php foreach (['USD','EUR','GBP','SAR','AED','TRY'] as $cur): ?>
        <option <?= setting('store_currency','USD') === $cur ? 'selected' : '' ?>><?= $cur ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save pricing</button>
  </form>

<?php elseif ($tab === 'subdomains'): ?>
  <h3 style="margin-top:0"><i class="fa-solid fa-sitemap"></i> cPanel account (for creating real subdomain sites)</h3>
  <p class="hint">From your hosting cPanel: Security &gt; Manage API Tokens &gt; Create. This token can create subdomains and databases on your account — keep it secret, and consider a token restricted to those two categories if your host supports scoped tokens.</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="subdomains">
    <div class="row2">
      <div><label>cPanel host</label><input type="text" name="cpanel_host" value="<?= e(setting('cpanel_host')) ?>" placeholder="yourserver.hostingcompany.com"></div>
      <div><label>cPanel username</label><input type="text" name="cpanel_username" value="<?= e(setting('cpanel_username')) ?>"></div>
    </div>
    <div class="row2">
      <div><label>API token</label><input type="password" name="cpanel_api_token" value="<?= e(setting('cpanel_api_token')) ?>"></div>
      <div><label>Root domain</label><input type="text" name="cpanel_root_domain" value="<?= e(setting('cpanel_root_domain')) ?>" placeholder="yassota.com"></div>
    </div>
    <label>Account home directory (absolute path)</label>
    <input type="text" name="cpanel_home_dir" value="<?= e(setting('cpanel_home_dir')) ?>" placeholder="/home/yourusername">
    <p class="hint">Find this in cPanel &gt; Files &gt; File Manager (the path shown when you open your home folder), or ask your host. Needed so this script can write the new site's files directly — subdomains under the same account share this server's filesystem.</p>
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
  </form>
  <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line)">
    <?php if (CPanelClient::isConfigured()): ?>
      <span class="badge ok"><i class="fa-solid fa-check"></i> cPanel connected — go to <a href="?page=subsites">Subdomain Sites</a> to create real subdomain sites.</span>
    <?php else: ?>
      <span class="badge off">Add your cPanel host, username and API token above to enable subdomain provisioning.</span>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'ads'): ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="ads">
    <label>AdSense Publisher ID</label>
    <input type="text" name="adsense_publisher_id" value="<?= e(setting('adsense_publisher_id')) ?>" placeholder="ca-pub-XXXXXXXXXXXXXXXX">
    <p class="hint">Ad units only render once you have a Publisher ID <em>and</em> a slot ID below for that placement. No ads show before AdSense approves the site — that review is done manually by Google.</p>
    <div class="row2">
      <?php foreach (['home_top'=>'Homepage — top','home_mid'=>'Homepage — middle','article_top'=>'Article — top','article_bottom'=>'Article — bottom','tool_top'=>'Tool page — top','tool_bottom'=>'Tool page — bottom'] as $slot=>$label): ?>
      <div><label><?= $label ?> slot ID</label><input type="text" name="slot_<?= $slot ?>" value="<?= e(setting('adsense_slot_' . $slot)) ?>"></div>
      <?php endforeach; ?>
    </div>
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
  </form>

<?php elseif ($tab === 'seo'): ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="seo">
    <label>Default keywords (used as a fallback)</label><input type="text" name="seo_default_keywords" value="<?= e(setting('seo_default_keywords')) ?>">
    <label>Google Search Console verification meta tag content</label><input type="text" name="google_site_verification" value="<?= e(setting('google_site_verification')) ?>">
    <label>Bing Webmaster verification meta tag content</label><input type="text" name="bing_site_verification" value="<?= e(setting('bing_site_verification')) ?>">
    <h3>IndexNow</h3>
    <label>IndexNow API Key</label>
    <input type="text" name="indexnow_key" value="<?= e(setting('indexnow_key')) ?>" placeholder="your-indexnow-key">
    <p class="hint">Get a free key at <a href="https://www.bing.com/indexnow" target="_blank" rel="noopener">bing.com/indexnow</a>. Once set, articles, tools, and pages ping Bing/Yandex automatically on publish. The key file <code>/{key}.txt</code> is auto-created in your webroot.</p>
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    <p class="hint" style="margin-top:14px">Sitemap: <code><?= e(site_url('sitemap.php')) ?></code> · Robots: <code><?= e(site_url('robots.php')) ?></code></p>
  </form>

<?php elseif ($tab === 'sitemap'): ?>
  <h3 style="margin-top:0"><i class="fa-solid fa-sitemap"></i> Sitemap &amp; Robots Generator</h3>
  <p class="hint">Generate physical <code>sitemap.xml</code> and <code>robots.txt</code> files at your webroot. The dynamic versions at <code>/sitemap.php</code> and <code>/robots.php</code> always work without this, but physical files load faster and some crawlers prefer them.</p>
  <?php
    $sitemapFile = ROOT_PATH . '/sitemap.xml';
    $robotsFile  = ROOT_PATH . '/robots.txt';
    $sitemapInfo = file_exists($sitemapFile) ? ['exists' => true, 'size' => filesize($sitemapFile), 'mtime' => filemtime($sitemapFile)] : ['exists' => false];
    $robotsInfo  = file_exists($robotsFile)  ? ['exists' => true, 'size' => filesize($robotsFile),  'mtime' => filemtime($robotsFile)]  : ['exists' => false];
  ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <div style="border:1px solid var(--line);border-radius:12px;padding:18px">
      <h4 style="margin:0 0 8px"><i class="fa-solid fa-file-code"></i> sitemap.xml</h4>
      <?php if ($sitemapInfo['exists']): ?>
        <p class="hint">Last generated: <?= date('M j, Y H:i', $sitemapInfo['mtime']) ?> · <?= number_format($sitemapInfo['size']) ?> bytes</p>
        <span class="badge ok"><i class="fa-solid fa-check"></i> File exists</span>
      <?php else: ?>
        <span class="badge off">Not generated yet</span>
      <?php endif; ?>
    </div>
    <div style="border:1px solid var(--line);border-radius:12px;padding:18px">
      <h4 style="margin:0 0 8px"><i class="fa-solid fa-robot"></i> robots.txt</h4>
      <?php if ($robotsInfo['exists']): ?>
        <p class="hint">Last generated: <?= date('M j, Y H:i', $robotsInfo['mtime']) ?> · <?= number_format($robotsInfo['size']) ?> bytes</p>
        <span class="badge ok"><i class="fa-solid fa-check"></i> File exists</span>
      <?php else: ?>
        <span class="badge off">Not generated yet</span>
      <?php endif; ?>
    </div>
  </div>
  <form method="post" style="display:inline-flex;gap:10px;flex-wrap:wrap">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="tab" value="sitemap">
    <button class="btn" name="sitemap_action" value="generate_sitemap"><i class="fa-solid fa-arrows-rotate"></i> Generate sitemap.xml</button>
    <button class="btn gray" name="sitemap_action" value="generate_robots"><i class="fa-solid fa-robot"></i> Generate robots.txt</button>
  </form>

<?php elseif ($tab === 'security'): ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="security">
    <label>Current password</label><input type="password" name="current_password" required>
    <label>New password (min 8 characters)</label><input type="password" name="new_password" required>
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-key"></i> Update password</button>
  </form>

<?php elseif ($tab === 'social'): ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="tab" value="social">
    <div class="row2">
      <div><label><i class="fa-brands fa-x-twitter"></i> Twitter / X URL</label><input type="text" name="social_twitter" value="<?= e(setting('social_twitter')) ?>" placeholder="https://x.com/yourhandle"></div>
      <div><label><i class="fa-brands fa-facebook-f"></i> Facebook URL</label><input type="text" name="social_facebook" value="<?= e(setting('social_facebook')) ?>" placeholder="https://facebook.com/yourpage"></div>
    </div>
    <div class="row2">
      <div><label><i class="fa-brands fa-linkedin-in"></i> LinkedIn URL</label><input type="text" name="social_linkedin" value="<?= e(setting('social_linkedin')) ?>" placeholder="https://linkedin.com/company/..."></div>
      <div><label><i class="fa-brands fa-youtube"></i> YouTube URL</label><input type="text" name="social_youtube" value="<?= e(setting('social_youtube')) ?>" placeholder="https://youtube.com/@yourchannel"></div>
    </div>
    <div class="row2">
      <div><label><i class="fa-brands fa-github"></i> GitHub URL</label><input type="text" name="social_github" value="<?= e(setting('social_github')) ?>" placeholder="https://github.com/yourorg"></div>
      <div><label><i class="fa-brands fa-instagram"></i> Instagram URL</label><input type="text" name="social_instagram" value="<?= e(setting('social_instagram')) ?>" placeholder="https://instagram.com/yourhandle"></div>
    </div>
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
  </form>
<?php endif; ?>
</div>
