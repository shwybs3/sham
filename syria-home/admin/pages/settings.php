<?php
$tab = $_GET['tab'] ?? 'general';
$tabs = ['general' => 'General', 'api' => 'API Keys', 'ads' => 'Advertisements', 'seo' => 'SEO', 'security' => 'Security', 'social' => 'Social Media'];
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $formTab = $_POST['tab'] ?? 'general';

    if ($formTab === 'general') {
        set_setting('site_name', trim($_POST['site_name'] ?? 'Syria Home'));
        set_setting('site_tagline', trim($_POST['site_tagline'] ?? ''));
        set_setting('site_description', trim($_POST['site_description'] ?? ''));
        $msg = ['ok', 'General settings saved.'];
    }

    if ($formTab === 'api') {
        foreach (['google_client_id','google_client_secret','google_ads_developer_token','google_ads_customer_id','ga4_property_id','gsc_site_url','gemini_api_key','gemini_model','openrouter_api_key'] as $k) {
            set_setting($k, trim($_POST[$k] ?? ''));
        }
        $msg = ['ok', 'API keys saved.'];
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
        $msg = ['ok', 'SEO settings saved.'];
    }

    if ($formTab === 'social') {
        foreach (['social_twitter','social_facebook','social_linkedin'] as $k) set_setting($k, trim($_POST[$k] ?? ''));
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
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
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
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    <p class="hint" style="margin-top:14px">Sitemap: <code><?= e(site_url('sitemap.php')) ?></code> · Robots: <code><?= e(site_url('robots.php')) ?></code></p>
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
    <label>Twitter / X URL</label><input type="text" name="social_twitter" value="<?= e(setting('social_twitter')) ?>">
    <label>Facebook URL</label><input type="text" name="social_facebook" value="<?= e(setting('social_facebook')) ?>">
    <label>LinkedIn URL</label><input type="text" name="social_linkedin" value="<?= e(setting('social_linkedin')) ?>">
    <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
  </form>
<?php endif; ?>
</div>
