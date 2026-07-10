<?php
require_once __DIR__ . '/config.php';

/* ══════════════════════════════════════════════════════
   AJAX: Generate AI data (multi-key × multi-model rotation)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate' && is_admin()) {
    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['name'] ?? '');
    if (!$name) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'اسم مطلوب']); exit; }

    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'لم يتم إضافة مفتاح OpenRouter بعد. أضفه من صفحة الإعدادات.']); exit; }

    $models = build_model_rotation($pdo);

    $prompt = <<<P
أنت خبير تسويق تطبيقات أندرويد. التطبيق: "{$name}"
أعد JSON صالح فقط بدون أي نص آخر أو Markdown:
{
  "name":"الاسم الرسمي",
  "seo_title":"عنوان SEO أقل من 60 حرف",
  "meta_description":"وصف meta أقل من 155 حرف",
  "keywords":"كلمات مفتاحية مفصولة بفاصلة",
  "short_description":"جملة أو جملتين",
  "long_description":"وصف طويل احترافي عدة فقرات",
  "developer":"اسم المطور المحتمل",
  "version":"رقم إصدار مثل 3.1.0",
  "android_version":"مثل: 7.0 فأعلى",
  "size_mb":"حجم تقريبي مثل 45",
  "license":"Free",
  "package_name":"com.developer.appname",
  "rating":4.5,
  "whats_new":"آخر التحديثات",
  "features":["ميزة 1","ميزة 2","ميزة 3","ميزة 4","ميزة 5"],
  "pros":["إيجابية 1","إيجابية 2","إيجابية 3"],
  "cons":["سلبية 1","سلبية 2"],
  "install_steps":["خطوة 1","خطوة 2","خطوة 3","خطوة 4"],
  "faq":[{"q":"سؤال شائع","a":"إجابة مفصلة"},{"q":"سؤال 2","a":"إجابة 2"},{"q":"سؤال 3","a":"إجابة 3"}]
}
P;

    $result = openrouter_call_rotating($keys, $models, $prompt);
    header('Content-Type: application/json');
    if (!$result['ok']) {
        echo json_encode([
            'success' => false,
            'error'   => 'فشل الاتصال بـ OpenRouter بعد تجربة ' . count($result['trace']) . ' محاولة (مفاتيح × موديلات). راجع صفحة "اختبار الاتصال" للتفاصيل.',
            'trace'   => $result['trace'],
        ]);
        exit;
    }

    $raw = trim(preg_replace(['/^```json\s*/m','/\s*```$/m','/^```\s*/m'], '', $result['content']));
    $s = strpos($raw, '{'); $e = strrpos($raw, '}');
    $data = ($s !== false && $e !== false) ? json_decode(substr($raw, $s, $e-$s+1), true) : null;

    if ($data) {
        $data['success'] = true;
        $data['used_model'] = $result['model'];
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success'=>false,'error'=>'الاتصال نجح لكن الرد لم يكن JSON صالح، حاول مجدداً (الموديل المستخدم: '.$result['model'].')']);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Test connection / full diagnostics
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'test_connection' && is_admin()) {
    header('Content-Type: application/json');
    $checks = [];

    // DB
    try { $pdo->query('SELECT 1'); $checks[] = ['label'=>'الاتصال بقاعدة البيانات','ok'=>true,'detail'=>'متصل بنجاح']; }
    catch (Throwable $e) { $checks[] = ['label'=>'الاتصال بقاعدة البيانات','ok'=>false,'detail'=>$e->getMessage()]; }

    // Tables
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $need = ['admins','categories','apps','settings'];
        $missing = array_diff($need, $tables);
        $checks[] = ['label'=>'جداول قاعدة البيانات','ok'=>empty($missing),'detail'=>$missing?('جداول ناقصة: '.implode(', ',$missing)):'كل الجداول موجودة'];
    } catch (Throwable $e) { $checks[] = ['label'=>'جداول قاعدة البيانات','ok'=>false,'detail'=>$e->getMessage()]; }

    // GD extension
    $checks[] = ['label'=>'مكتبة معالجة الصور (GD)','ok'=>extension_loaded('gd'),'detail'=>extension_loaded('gd')?'مفعّلة':'غير مفعّلة — لن تعمل معالجة الأيقونات'];

    // cURL extension
    $checks[] = ['label'=>'مكتبة cURL','ok'=>extension_loaded('curl'),'detail'=>extension_loaded('curl')?'مفعّلة':'غير مفعّلة — لن يعمل الاتصال بـ OpenRouter'];

    // uploads writable
    $upOk = is_dir(UPLOAD_PATH) && is_writable(UPLOAD_PATH);
    if (!is_dir(UPLOAD_PATH)) { @mkdir(UPLOAD_PATH, 0755, true); $upOk = is_dir(UPLOAD_PATH) && is_writable(UPLOAD_PATH); }
    $checks[] = ['label'=>'صلاحية الكتابة في مجلد uploads','ok'=>$upOk,'detail'=>$upOk?'قابل للكتابة':'غير قابل للكتابة — غيّر الصلاحيات إلى 755'];

    // OpenRouter
    $keys = openrouter_keys(get_cfg($pdo,'openrouter_key'));
    if (!$keys) {
        $checks[] = ['label'=>'مفتاح OpenRouter API','ok'=>false,'detail'=>'لم يتم إضافة أي مفتاح بعد'];
    } else {
        $models = build_model_rotation($pdo, true);
        $r = openrouter_call_rotating($keys, $models, 'قل "مرحبا" فقط بدون أي إضافات.');
        $checks[] = [
            'label' => 'الاتصال بـ OpenRouter (' . count($keys) . ' مفتاح × ' . count($models) . ' موديل)',
            'ok' => $r['ok'],
            'detail' => $r['ok'] ? ('نجح الاتصال باستخدام الموديل: '.$r['model']) : 'فشلت كل المحاولات — راجع تفاصيل الأخطاء أدناه',
            'trace' => $r['trace'],
            'working_model' => $r['ok'] ? $r['model'] : null,
        ];
        // Auto-save the first working model as primary for next time
        $primaryNow = get_cfg($pdo, 'openrouter_model', '');
        if ($r['ok'] && $r['model'] !== $primaryNow) {
            set_cfg($pdo, 'openrouter_model', $r['model']);
        }
    }

    $allOk = !array_filter($checks, fn($c) => !$c['ok']);
    echo json_encode(['success'=>true,'all_ok'=>$allOk,'checks'=>$checks], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: List free OpenRouter models
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list_models' && is_admin()) {
    header('Content-Type: application/json');
    $models = fetch_openrouter_free_models();
    if (!$models) {
        $models = array_map(fn($id) => ['id'=>$id,'name'=>$id,'context_length'=>null], openrouter_default_free_models());
    }
    echo json_encode(['success'=>true,'models'=>$models], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Repair database (re-run schema installer)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fix_db' && is_admin()) {
    header('Content-Type: application/json');
    try {
        $log = ensure_schema($pdo);
        echo json_encode(['success'=>true,'message'=>'تم فحص/إصلاح الجداول بنجاح','tables'=>$log], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Regenerate SEO fields for one app (used by bulk tool)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'regen_seo' && is_admin()) {
    header('Content-Type: application/json');
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id,name,short_description FROM apps WHERE id=?");
    $stmt->execute([$id]);
    $app = $stmt->fetch();
    if (!$app) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }

    $prompt = <<<P
أنت خبير SEO محترف لمتاجر التطبيقات العربية. التطبيق: "{$app['name']}"
الوصف الحالي: "{$app['short_description']}"
أعد JSON صالح فقط بدون أي نص إضافي:
{"seo_title":"عنوان SEO جذاب أقل من 60 حرف يتضمن اسم التطبيق وكلمة مفتاحية قوية",
"meta_description":"وصف Meta مقنع أقل من 155 حرف يحفّز على النقر",
"keywords":"12-15 كلمة مفتاحية عربية قوية مفصولة بفاصلة، منها كلمات طويلة الذيل"}
P;
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $data = ai_extract_json($r['content']);
    if (!$data) { echo json_encode(['success'=>false,'error'=>'رد غير صالح من الموديل']); exit; }

    $pdo->prepare("UPDATE apps SET seo_title=?, meta_description=?, keywords=? WHERE id=?")
        ->execute([trim($data['seo_title'] ?? ''), trim($data['meta_description'] ?? ''), trim($data['keywords'] ?? ''), $id]);
    echo json_encode(['success'=>true,'name'=>$app['name']], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: List published app ids for bulk SEO tool
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'bulk_seo_list' && is_admin()) {
    header('Content-Type: application/json');
    $ids = $pdo->query("SELECT id FROM apps WHERE status='published' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['success'=>true,'ids'=>array_map('intval',$ids)]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Continue long description content (~+600 words each click)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'continue_content' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name    = trim($input['name'] ?? '');
    $current = trim($input['current'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم التطبيق مطلوب']); exit; }

    $tail = mb_substr($current, -1200);
    $prompt = <<<P
أنت كاتب محتوى SEO محترف متخصص في متاجر التطبيقات العربية. تكتب وصفاً طويلاً واحترافياً وأصلياً (وليس مكرراً أو محشواً) للتطبيق "{$name}"، بأسلوب طبيعي ومتدفق يفيد القارئ ويرفع ترتيب الصفحة في محركات البحث.
هذا هو آخر جزء مكتوب حتى الآن (للسياق فقط، لا تكرره):
"""{$tail}"""
أكمل الوصف مباشرة من حيث توقف (بدون تكرار ما سبق وبدون مقدمات مثل "بالتأكيد" أو "إليك") بفقرة أو أكثر أصلية بطول تقريبي 500-700 كلمة، تغطي جوانب لم تُذكر بعد مثل: تفاصيل الأداء، حالات استخدام واقعية، مقارنة موجزة مع بدائل، نصائح استخدام، الأمان والخصوصية، أو الفئة المستهدفة. أعد النص فقط بدون أي تنسيق Markdown وبدون عناوين JSON — فقرات نصية عادية فقط.
P;
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $addition = trim(preg_replace('/^```\w*|```$/m', '', $r['content']));
    echo json_encode(['success'=>true,'addition'=>$addition,'added_words'=>str_word_count($addition)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate privacy policy / terms / "what this app offers"
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_legal' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $type = trim($input['type'] ?? '');
    if (!$name || !in_array($type, ['privacy','terms','offers'], true)) {
        echo json_encode(['success'=>false,'error'=>'بيانات غير صالحة']); exit;
    }

    $prompts = [
        'privacy' => "اكتب سياسة خصوصية احترافية وشاملة باللغة العربية لصفحة تحميل تطبيق أندرويد اسمه \"{$name}\" على موقع متجر تطبيقات (وليس المطور الفعلي). غطِّ: نوع البيانات التي قد يجمعها التطبيق، كيفية استخدامها، ملفات تعريف الارتباط والإعلانات، مشاركة البيانات مع أطراف ثالثة، حقوق المستخدم، أمان البيانات، وطريقة التواصل. اكتب نصاً منسقاً بفقرات وعناوين فرعية واضحة (استخدم ## للعناوين) بدون Markdown معقد، بطول 500-800 كلمة تقريباً. أعد النص فقط بدون أي مقدمات.",
        'terms'   => "اكتب شروط استخدام احترافية باللغة العربية لصفحة تحميل تطبيق أندرويد اسمه \"{$name}\" على موقع متجر تطبيقات. غطِّ: قبول الشروط، طريقة الاستخدام المسموح بها، إخلاء المسؤولية عن التطبيق كونه من طرف ثالث، حقوق الملكية الفكرية، حدود المسؤولية، والتعديلات على الشروط. اكتب نصاً منسقاً بعناوين فرعية (## للعناوين) بطول 400-700 كلمة. أعد النص فقط بدون أي مقدمات.",
        'offers'  => "اكتب فقرة تسويقية احترافية بعنوان \"ماذا يقدم تطبيق {$name}؟\" تشرح بشكل مقنع وواقعي أبرز ما يقدمه هذا التطبيق للمستخدم العربي، بأسلوب سلس يخدم SEO، بطول 200-300 كلمة، فقرة نصية عادية بدون عناوين Markdown.",
    ];
    $r = ai_text($pdo, $prompts[$type]);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $content = trim(preg_replace('/^```\w*|```$/m', '', $r['content']));
    echo json_encode(['success'=>true,'content'=>$content,'type'=>$type], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Import basic app info from a Google Play Store URL
   (public Open Graph tags only — description/icon/title;
   download link must still be added manually since Play
   Store does not expose a direct APK link).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) === true && $_GET['ajax'] === 'fetch_playstore' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $src = trim($input['url'] ?? '');
    if (!$src || !preg_match('#^https://play\.google\.com/store/apps/details#', $src)) {
        echo json_encode(['success'=>false,'error'=>'ضع رابط صفحة تطبيق صالح من play.google.com/store/apps/details']); exit;
    }

    $ch = curl_init($src);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Accept-Language: ar,en;q=0.8'],
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$html || $code !== 200) {
        echo json_encode(['success'=>false,'error'=>'تعذر جلب الصفحة من Google Play (رمز: '.$code.')']); exit;
    }

    $meta = function(string $prop) use ($html): ?string {
        if (preg_match('#<meta[^>]+property=["\']' . preg_quote($prop, '#') . '["\'][^>]+content=["\']([^"\']*)["\']#i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        if (preg_match('#<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']' . preg_quote($prop, '#') . '["\']#i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        return null;
    };

    $title = $meta('og:title');
    $desc  = $meta('og:description');
    $image = $meta('og:image');
    $pkg = null;
    if (preg_match('#[?&]id=([a-zA-Z0-9_.]+)#', $src, $m)) $pkg = $m[1];

    if (!$title && !$desc) {
        echo json_encode(['success'=>false,'error'=>'لم يتم العثور على بيانات في الصفحة — قد تكون Google تمنع الوصول الآلي من هذا الاستضافة']); exit;
    }

    // Clean up common Play Store title suffix "- Apps on Google Play"
    if ($title) $title = trim(preg_replace('/\s*[-–]\s*(Apps on Google Play|تطبيقات على Google Play).*$/i', '', $title));

    echo json_encode([
        'success' => true,
        'name' => $title,
        'short_description' => $desc ? mb_substr($desc, 0, 300) : null,
        'long_description' => $desc,
        'icon_url' => $image,
        'package_name' => $pkg,
        'playstore_url' => $src,
        'note' => 'تم استيراد العنوان والوصف والأيقونة فقط. رابط التحميل المباشر غير متاح من Google Play — أضفه يدوياً أو استخدم توليد AI لباقي الحقول.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$page   = $_GET['page'] ?? 'dashboard';
$msg    = '';
$error  = '';

// Login action
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { $error = 'جلسة غير صالحة'; }
    else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username=?");
        $stmt->execute([trim($_POST['username'] ?? '')]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($_POST['password'] ?? '', $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_user'] = $admin['username'];
            header('Location: admin.php'); exit;
        }
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    }
}

// Logout
if ($page === 'logout') { session_destroy(); header('Location: admin.php?page=login'); exit; }

// Require login for everything except login page
if ($page !== 'login') require_admin();

/* ══════════════════════════════════════════════════════
   Actions
   ══════════════════════════════════════════════════════ */

// ─── Save settings ───
if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    foreach (['openrouter_key','openrouter_model','openrouter_fallback','moneytag_zone'] as $k) {
        set_cfg($pdo, $k, trim($_POST[$k] ?? ''));
    }
    set_cfg($pdo, 'openrouter_auto_rotate', isset($_POST['openrouter_auto_rotate']) ? '1' : '0');
    $msg = 'تم حفظ الإعدادات';
}

// ─── Delete app ───
if ($page === 'apps' && isset($_GET['del']) && isset($_GET['t']) &&
    hash_equals($_SESSION['csrf'] ?? '', $_GET['t'])) {
    $pdo->prepare("DELETE FROM apps WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: admin.php?page=apps&msg=deleted'); exit;
}

// ─── Save / Update app ───
if (in_array($page, ['add-app','edit-app']) && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $isEdit = $page === 'edit-app';
    $appId  = (int)($_POST['app_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');

    if (!$name) { $error = 'اسم التطبيق مطلوب'; goto render; }

    // Slug
    if ($isEdit && $appId) {
        $existing = $pdo->prepare("SELECT slug,icon_path,screenshots FROM apps WHERE id=?");
        $existing->execute([$appId]); $existing = $existing->fetch();
        $slug = $existing['slug'];
    } else {
        $slug = unique_slug($pdo, $name);
        $existing = null;
    }

    // Icon
    $iconPath = $existing['icon_path'] ?? null;
    if (!empty($_FILES['icon']['tmp_name']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $p = process_icon($_FILES['icon'], $slug);
        if ($p) $iconPath = $p;
    } elseif (!empty($_POST['icon_url_import'])) {
        // Icon was imported from Google Play — download it server-side and process it the same way.
        $remote = filter_var(trim($_POST['icon_url_import']), FILTER_VALIDATE_URL);
        if ($remote) {
            $tmp = tempnam(sys_get_temp_dir(), 'icn');
            $ch = curl_init($remote);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => true]);
            $bin = curl_exec($ch);
            $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200 && $bin;
            curl_close($ch);
            if ($ok) {
                file_put_contents($tmp, $bin);
                $fakeFile = ['tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($bin)];
                $p = process_icon($fakeFile, $slug);
                if ($p) $iconPath = $p;
            }
            @unlink($tmp);
        }
    }

    // Screenshots
    $shots = json_decode($existing['screenshots'] ?? '[]', true) ?: [];
    if (!empty($_FILES['screenshots']['name'][0])) {
        $newShots = process_screenshots($_FILES['screenshots'], $slug);
        $shots = array_merge($shots, $newShots);
    }

    // Build JSON fields
    $features     = array_values(array_filter($_POST['features'] ?? []));
    $pros         = array_values(array_filter($_POST['pros'] ?? []));
    $cons         = array_values(array_filter($_POST['cons'] ?? []));
    $installSteps = array_values(array_filter($_POST['install_steps'] ?? []));
    $faqQ = $_POST['faq']['q'] ?? [];
    $faqA = $_POST['faq']['a'] ?? [];
    $faq  = [];
    for ($fi = 0; $fi < count($faqQ); $fi++) {
        if (trim($faqQ[$fi])) $faq[] = ['q' => trim($faqQ[$fi]), 'a' => trim($faqA[$fi] ?? '')];
    }

    $downloadUrl = trim($_POST['download_url'] ?? '');
    $requestedStatus = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
    $forcedDraft = false;
    if ($requestedStatus === 'published' && $downloadUrl === '') {
        $requestedStatus = 'draft';
        $forcedDraft = true;
    }

    $d = [
        'name'              => $name,
        'seo_title'         => trim($_POST['seo_title'] ?? ''),
        'meta_description'  => trim($_POST['meta_description'] ?? ''),
        'keywords'          => trim($_POST['keywords'] ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'long_description'  => trim($_POST['long_description'] ?? ''),
        'privacy_policy'    => trim($_POST['privacy_policy'] ?? ''),
        'terms_content'     => trim($_POST['terms_content'] ?? ''),
        'offers_text'       => trim($_POST['offers_text'] ?? ''),
        'developer'         => trim($_POST['developer'] ?? ''),
        'version'           => trim($_POST['version'] ?? ''),
        'play_store_version'=> trim($_POST['play_store_version'] ?? ''),
        'playstore_url'     => trim($_POST['playstore_url'] ?? ''),
        'android_version'   => trim($_POST['android_version'] ?? ''),
        'size_mb'           => trim($_POST['size_mb'] ?? ''),
        'license'           => trim($_POST['license'] ?? 'Free'),
        'package_name'      => trim($_POST['package_name'] ?? ''),
        'category_id'       => ($_POST['category_id'] ?? '') ?: null,
        'rating'            => (float)($_POST['rating'] ?? 4.5),
        'download_url'      => $downloadUrl,
        'mirror2_url'       => trim($_POST['mirror2_url'] ?? ''),
        'mirror3_url'       => trim($_POST['mirror3_url'] ?? ''),
        'whats_new'         => trim($_POST['whats_new'] ?? ''),
        'status'            => $requestedStatus,
        'needs_update'      => isset($_POST['needs_update']) ? 1 : 0,
        'icon_path'         => $iconPath,
        'screenshots'       => json_encode($shots, JSON_UNESCAPED_UNICODE),
        'features'          => json_encode($features, JSON_UNESCAPED_UNICODE),
        'pros'              => json_encode($pros, JSON_UNESCAPED_UNICODE),
        'cons'              => json_encode($cons, JSON_UNESCAPED_UNICODE),
        'install_steps'     => json_encode($installSteps, JSON_UNESCAPED_UNICODE),
        'faq'               => json_encode($faq, JSON_UNESCAPED_UNICODE),
    ];

    if ($isEdit && $appId) {
        $sets = implode(', ', array_map(fn($k) => "$k=:$k", array_keys($d)));
        $d['id'] = $appId;
        $pdo->prepare("UPDATE apps SET $sets WHERE id=:id")->execute($d);
        header('Location: admin.php?page=apps&msg=' . ($forcedDraft ? 'updated_no_link' : 'updated')); exit;
    } else {
        $d['slug'] = $slug;
        $cols = implode(',', array_keys($d));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($d)));
        $pdo->prepare("INSERT INTO apps ($cols) VALUES ($vals)")->execute($d);
        header('Location: admin.php?page=apps&msg=' . ($forcedDraft ? 'added_no_link' : 'added')); exit;
    }
}

// ─── Categories ───
if ($page === 'categories') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
        $n = trim($_POST['name'] ?? '');
        if ($n) $pdo->prepare("INSERT IGNORE INTO categories (name,slug,sort_order) VALUES(?,?,?)")
            ->execute([$n, slugify($n), (int)($_POST['sort_order']??0)]);
    }
    if (isset($_GET['del_cat']) && isset($_GET['t']) && hash_equals($_SESSION['csrf']??'', $_GET['t'])) {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_GET['del_cat']]);
        header('Location: admin.php?page=categories&msg=deleted'); exit;
    }
}

// ─── Fetch data for forms ───
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$editApp = null;
if ($page === 'edit-app' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editApp = $stmt->fetch();
}

// Dashboard stats
$stats = [];
if ($page === 'dashboard') {
    $stats = [
        'apps'      => (int)$pdo->query("SELECT COUNT(*) FROM apps")->fetchColumn(),
        'published' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn(),
        'views'     => (int)$pdo->query("SELECT COALESCE(SUM(views),0) FROM apps")->fetchColumn(),
        'downloads' => (int)$pdo->query("SELECT COALESCE(SUM(downloads),0) FROM apps")->fetchColumn(),
    ];
    $recentApps = $pdo->query("SELECT a.*,c.name AS cat FROM apps a LEFT JOIN categories c ON a.category_id=c.id ORDER BY a.created_at DESC LIMIT 8")->fetchAll();
    $topApps    = $pdo->query("SELECT name,slug,views,downloads FROM apps ORDER BY views DESC LIMIT 5")->fetchAll();
    $needsUpdateApps = $pdo->query("SELECT id,name,slug,version,updated_at FROM apps WHERE needs_update=1 ORDER BY updated_at ASC LIMIT 10")->fetchAll();
}

if (isset($_GET['msg'])) {
    $msgs = [
        'added'=>'تمت الإضافة بنجاح',
        'updated'=>'تم التحديث بنجاح',
        'deleted'=>'تم الحذف',
        'added_no_link'=>'تمت الإضافة لكن كمسودة: لم يعمل النشر لأنه لا يوجد رابط تحميل. أضف رابط التحميل ثم انشر التطبيق.',
        'updated_no_link'=>'تم التحديث لكن التطبيق أصبح مسودة: لا يوجد رابط تحميل. أضف رابط التحميل ثم انشر التطبيق.',
    ];
    $msg = $msgs[$_GET['msg']] ?? '';
}

render:
/* ══════════════════════════════════════════════════════
   LOGIN PAGE
   ══════════════════════════════════════════════════════ */
if ($page === 'login'): ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>تسجيل الدخول — yassota admin</title>
  <meta name="robots" content="noindex">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="admin-login">
  <div class="login-box">
    <h1>yass<span>ota</span></h1>
    <p>لوحة التحكم</p>
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" action="admin.php?page=login">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">اسم المستخدم</label>
        <input class="form-input" type="text" name="username" required autofocus>
      </div>
      <div class="form-group" style="margin-bottom:24px">
        <label class="form-label">كلمة المرور</label>
        <input class="form-input" type="password" name="password" required>
      </div>
      <button type="submit" class="btn-save" style="width:100%;justify-content:center">دخول</button>
    </form>
  </div>
</div>
</body>
</html>
<?php exit; endif; ?>

<?php
/* ══════════════════════════════════════════════════════
   MAIN ADMIN LAYOUT
   ══════════════════════════════════════════════════════ */
$navLinks = [
    'dashboard' => ['label'=>'لوحة التحكم',   'icon'=>'M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-4h4v4h4a1 1 0 001-1v-9'],
    'apps'      => ['label'=>'التطبيقات',     'icon'=>'M4 6h16M4 12h16M4 18h16'],
    'add-app'   => ['label'=>'إضافة تطبيق',   'icon'=>'M12 5v14m-7-7h14'],
    'categories'=> ['label'=>'التصنيفات',     'icon'=>'M3 7h4v4H3V7zm0 6h4v4H3v-4zm6-6h12v4H9V7zm0 6h12v4H9v-4z'],
    'connection'=> ['label'=>'اختبار الاتصال', 'icon'=>'M13 10V3L4 14h7v7l9-11h-7z'],
    'database'  => ['label'=>'قاعدة البيانات', 'icon'=>'M4 6c0-1.1 3.6-2 8-2s8 .9 8 2-3.6 2-8 2-8-.9-8-2zm0 0v12c0 1.1 3.6 2 8 2s8-.9 8-2V6M4 12c0 1.1 3.6 2 8 2s8-.9 8-2'],
    'settings'  => ['label'=>'الإعدادات',     'icon'=>'M12 15a3 3 0 100-6 3 3 0 000 6zm0 0v3m0-12V3m9 9h-3M6 12H3m15.364-6.364l-2.121 2.121M8.757 15.243l-2.121 2.121M18.364 18.364l-2.121-2.121M8.757 8.757L6.636 6.636'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($navLinks[$page]['label'] ?? 'Admin') ?> — yassota</title>
  <meta name="robots" content="noindex">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<!-- ═══ MOBILE TOPBAR (يظهر فقط على الشاشات الصغيرة) ═══ -->
<div class="admin-mobile-topbar">
  <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="القائمة" aria-expanded="false">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
  </button>
  <div class="admin-logo" style="padding:0;border:none;margin:0;font-size:16px">yass<span>ota</span></div>
  <a href="admin.php?page=logout" style="color:var(--danger)">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9"/></svg>
  </a>
</div>
<div class="admin-sidebar-overlay" id="admin-sidebar-overlay"></div>

<div class="admin-wrap">

<!-- ═══ SIDEBAR ═══ -->
<aside class="admin-sidebar" id="admin-sidebar">
  <div class="admin-logo">yass<span>ota</span></div>
  <nav class="admin-nav">
    <?php foreach ($navLinks as $key => $nav): ?>
    <a href="admin.php?page=<?= $key ?>" class="<?= $page === $key ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="<?= $nav['icon'] ?>"/>
      </svg>
      <?= $nav['label'] ?>
    </a>
    <?php endforeach; ?>
    <a href="index.php" target="_blank" style="margin-top:auto">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      عرض الموقع
    </a>
    <a href="admin.php?page=logout" style="color:var(--danger)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9"/></svg>
      خروج
    </a>
  </nav>
</aside>

<!-- ═══ MAIN ═══ -->
<div class="admin-main">

<?php if ($msg): ?><div class="alert <?= (isset($_GET['msg']) && str_ends_with($_GET['msg'], '_no_link')) ? 'alert-error' : 'alert-success' ?>"><?= h($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

<?php
/* ─────────────── DASHBOARD ─────────────── */
if ($page === 'dashboard'): ?>

<div class="admin-header">
  <h1>لوحة التحكم</h1>
  <a href="admin.php?page=add-app" class="btn-save">+ إضافة تطبيق</a>
</div>

<div class="admin-stats">
  <div class="stat-card"><div class="stat-num"><?= number_format($stats['apps']) ?></div><div class="stat-label">إجمالي التطبيقات</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= number_format($stats['published']) ?></div><div class="stat-label">منشور</div></div>
  <div class="stat-card"><div class="stat-num"><?= number_format($stats['views']) ?></div><div class="stat-label">إجمالي المشاهدات</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--purple)"><?= number_format($stats['downloads']) ?></div><div class="stat-label">إجمالي التحميلات</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
  <div class="panel">
    <h2>الأكثر زيارة</h2>
    <table class="admin-table">
      <thead><tr><th>التطبيق</th><th>مشاهدات</th><th>تحميلات</th></tr></thead>
      <tbody>
        <?php foreach ($topApps as $a): ?>
        <tr>
          <td><?= h($a['name']) ?></td>
          <td style="font-family:var(--f-mono)"><?= number_format($a['views']) ?></td>
          <td style="font-family:var(--f-mono)"><?= number_format($a['downloads']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$topApps): ?><tr><td colspan="3" style="color:var(--muted)">لا توجد بيانات بعد</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel">
    <h2>أحدث الإضافات</h2>
    <table class="admin-table">
      <thead><tr><th>التطبيق</th><th>التصنيف</th><th>الحالة</th></tr></thead>
      <tbody>
        <?php foreach ($recentApps as $a): ?>
        <tr>
          <td><?= h($a['name']) ?></td>
          <td style="font-size:11px;color:var(--muted)"><?= h($a['cat'] ?? '—') ?></td>
          <td><span class="status-badge status-<?= $a['status'] ?>"><?= $a['status'] === 'published' ? 'منشور' : 'مسودة' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($needsUpdateApps): ?>
<div class="panel" style="border-color:rgba(251,191,36,.3);margin-top:20px">
  <h2 style="color:#fbbf24">⚠️ تطبيقات تحتاج تحديث (<?= count($needsUpdateApps) ?>)</h2>
  <table class="admin-table responsive-cards">
    <thead><tr><th>التطبيق</th><th>الإصدار الحالي</th><th>آخر تحديث</th><th>إجراء</th></tr></thead>
    <tbody>
      <?php foreach ($needsUpdateApps as $a): ?>
      <tr>
        <td data-label="التطبيق" style="font-weight:700"><?= h($a['name']) ?></td>
        <td data-label="الإصدار" style="font-family:var(--f-mono)"><?= h($a['version'] ?: '—') ?></td>
        <td data-label="آخر تحديث" style="color:var(--muted);font-size:12px"><?= h(time_ago($a['updated_at'])) ?></td>
        <td data-label="إجراء" class="td-actions">
          <a href="admin.php?page=edit-app&id=<?= $a['id'] ?>" class="btn-edit">تحديث الآن</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="panel" style="margin-top:20px">
  <h2>أدوات SEO الجماعية</h2>
  <p style="color:var(--muted);font-size:13px;margin-bottom:14px">
    إعادة توليد عنوان SEO ووصف Meta والكلمات المفتاحية لكل التطبيقات المنشورة دفعة واحدة بالذكاء الاصطناعي — مفيد بعد تغيير استراتيجية الكلمات المفتاحية.
  </p>
  <button type="button" id="btn-bulk-seo" class="btn-ai">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    تحديث SEO لكل التطبيقات
  </button>
  <div id="bulk-seo-progress" style="margin-top:14px;display:none">
    <div style="height:8px;background:var(--navy-600);border-radius:6px;overflow:hidden">
      <div id="bulk-seo-bar" style="height:100%;width:0%;background:linear-gradient(135deg,var(--cyan),var(--purple));transition:width .3s"></div>
    </div>
    <div id="bulk-seo-status" style="font-size:12px;color:var(--muted);margin-top:8px"></div>
  </div>
</div>

<?php
/* ─────────────── APPS LIST ─────────────── */
elseif ($page === 'apps'):
  $search = trim($_GET['q'] ?? '');
  $appsList = $search
    ? $pdo->prepare("SELECT a.*,c.name AS cat FROM apps a LEFT JOIN categories c ON a.category_id=c.id WHERE a.name LIKE ? ORDER BY a.created_at DESC LIMIT 100")
    : $pdo->query("SELECT a.*,c.name AS cat FROM apps a LEFT JOIN categories c ON a.category_id=c.id ORDER BY a.created_at DESC LIMIT 100");
  if ($search) { $appsList->execute(["%$search%"]); $appsList = $appsList->fetchAll(); }
  else $appsList = $appsList->fetchAll();
?>

<div class="admin-header">
  <h1>التطبيقات</h1>
  <a href="admin.php?page=add-app" class="btn-save">+ إضافة تطبيق</a>
</div>
<form method="get" action="admin.php" class="admin-search-row">
  <input type="hidden" name="page" value="apps">
  <input class="form-input" type="text" name="q" placeholder="بحث بالاسم..." value="<?= h($search) ?>">
  <button type="submit" class="btn-save" style="padding:11px 24px">بحث</button>
</form>

<div class="panel" style="padding:0;overflow:hidden">
<table class="admin-table responsive-cards">
  <thead>
    <tr>
      <th style="width:44px"></th>
      <th>التطبيق</th><th>التصنيف</th><th>إصدار</th><th>حجم</th>
      <th>مشاهدات</th><th>تحميلات</th><th>الحالة</th><th>إجراءات</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($appsList as $a): ?>
  <tr>
    <td class="td-thumb">
      <?php if ($a['icon_path']): ?>
        <img src="<?= h($a['icon_path']) ?>" class="app-thumb" alt="">
      <?php else: ?>
        <div class="app-thumb-placeholder"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="3"/></svg></div>
      <?php endif; ?>
    </td>
    <td data-label="التطبيق" style="font-weight:700"><?= h($a['name']) ?></td>
    <td data-label="التصنيف" style="color:var(--muted);font-size:12px"><?= h($a['cat'] ?? '—') ?></td>
    <td data-label="إصدار" style="font-family:var(--f-mono);font-size:12px"><?= h($a['version'] ?? '—') ?></td>
    <td data-label="حجم" style="font-family:var(--f-mono);font-size:12px"><?= $a['size_mb'] ? h($a['size_mb']).' MB' : '—' ?></td>
    <td data-label="مشاهدات" style="font-family:var(--f-mono)"><?= number_format($a['views']) ?></td>
    <td data-label="تحميلات" style="font-family:var(--f-mono)"><?= number_format($a['downloads']) ?></td>
    <td data-label="الحالة">
      <span class="status-badge status-<?= $a['status'] ?>"><?= $a['status']==='published'?'منشور':'مسودة' ?></span>
      <?php if (!empty($a['needs_update'])): ?><span class="status-badge status-draft" style="border-color:rgba(251,191,36,.3);color:#fbbf24;background:rgba(251,191,36,.1)">يحتاج تحديث</span><?php endif; ?>
    </td>
    <td data-label="إجراءات" class="td-actions">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="admin.php?page=edit-app&id=<?= $a['id'] ?>" class="btn-edit">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          تعديل / تحديث
        </a>
        <a href="app.php?slug=<?= h($a['slug']) ?>" target="_blank" class="btn-view">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          عرض
        </a>
        <a href="admin.php?page=apps&del=<?= $a['id'] ?>&t=<?= csrf_token() ?>"
           class="btn-del" data-confirm="تأكيد حذف «<?= h($a['name']) ?>»؟">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
          حذف
        </a>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$appsList): ?><tr><td colspan="9" style="text-align:center;color:var(--muted);padding:32px">لا توجد تطبيقات</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php
/* ─────────────── ADD / EDIT APP ─────────────── */
elseif ($page === 'add-app' || $page === 'edit-app'):
  $app = $editApp;
  $isEdit = $page === 'edit-app' && $app;
  $feat   = json_decode($app['features']     ?? '[]', true) ?: [];
  $pros   = json_decode($app['pros']         ?? '[]', true) ?: [];
  $cons   = json_decode($app['cons']         ?? '[]', true) ?: [];
  $steps  = json_decode($app['install_steps']?? '[]', true) ?: [];
  $faqArr = json_decode($app['faq']          ?? '[]', true) ?: [];
?>

<div class="admin-header">
  <h1><?= $isEdit ? 'تعديل: '.h($app['name']) : 'إضافة تطبيق جديد' ?></h1>
  <a href="admin.php?page=apps" class="btn-edit">← كل التطبيقات</a>
</div>

<!-- Import from Google Play -->
<div class="ai-box" style="--border-p: rgba(0,245,255,.25)">
  <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--cyan)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-left:4px"><path d="M4 3.5v17l14-8.5-14-8.5z"/></svg>
    استيراد بيانات أولية من رابط Google Play
  </div>
  <div class="ai-row">
    <input class="form-input" id="playstore-import-url" type="text" placeholder="https://play.google.com/store/apps/details?id=...">
    <button type="button" id="btn-import-playstore" class="btn-ai">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
      استيراد
    </button>
  </div>
  <div class="ai-status" id="playstore-import-status">يستورد العنوان، الوصف، والأيقونة فقط — رابط التحميل المباشر غير متاح من Google Play ويجب إضافته يدوياً.</div>
</div>

<!-- AI Generate -->
<div class="ai-box">
  <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--purple)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-left:4px"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    توليد بيانات التطبيق تلقائياً بالذكاء الاصطناعي
  </div>
  <div class="ai-row">
    <input class="form-input" id="ai-name" type="text" placeholder="اكتب اسم التطبيق..." value="<?= h($app['name'] ?? '') ?>">
    <button type="button" id="btn-ai" class="btn-ai">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      Generate AI
    </button>
  </div>
  <div class="ai-status" id="ai-status"></div>
</div>

<form method="post" action="admin.php?page=<?= $page ?><?= $isEdit ? '&id='.$app['id'] : '' ?>"
      enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?php if ($isEdit): ?><input type="hidden" name="app_id" value="<?= $app['id'] ?>"><?php endif; ?>

  <!-- ── Basic Info ── -->
  <div class="panel">
    <h2>المعلومات الأساسية</h2>
    <div class="form-grid">
      <div class="form-group full">
        <label class="form-label">اسم التطبيق *</label>
        <input class="form-input" id="f-name" type="text" name="name" value="<?= h($app['name']??'') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">SEO Title</label>
        <input class="form-input" id="f-seo-title" type="text" name="seo_title" value="<?= h($app['seo_title']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">الكلمات المفتاحية</label>
        <input class="form-input" id="f-keywords" type="text" name="keywords" value="<?= h($app['keywords']??'') ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">Meta Description</label>
        <input class="form-input" id="f-meta-desc" type="text" name="meta_description" value="<?= h($app['meta_description']??'') ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">وصف قصير</label>
        <input class="form-input" id="f-short-desc" type="text" name="short_description" value="<?= h($app['short_description']??'') ?>">
      </div>
      <div class="form-group full">
        <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
          <span>وصف مطوّل <span id="desc-word-count" style="color:var(--muted);font-weight:400"></span></span>
          <button type="button" id="btn-continue-desc" class="add-item-btn" style="margin:0;padding:6px 14px;font-size:12px">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14m-7-7h14"/></svg>
            متابعة الكتابة (+٦٠٠ كلمة)
          </button>
        </label>
        <textarea class="form-textarea" id="f-long-desc" name="long_description" rows="8"><?= h($app['long_description']??'') ?></textarea>
        <div class="form-hint" id="continue-desc-status"></div>
      </div>
    </div>
  </div>

  <!-- ── Technical Info ── -->
  <div class="panel">
    <h2>البيانات التقنية</h2>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">المطور</label>
        <input class="form-input" id="f-developer" type="text" name="developer" value="<?= h($app['developer']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">الإصدار الحالي</label>
        <input class="form-input" id="f-version" type="text" name="version" value="<?= h($app['version']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">إصدار Google Play</label>
        <input class="form-input" type="text" name="play_store_version" value="<?= h($app['play_store_version']??'') ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">رابط صفحة Google Play</label>
        <input class="form-input" type="text" name="playstore_url" value="<?= h($app['playstore_url']??'') ?>" placeholder="https://play.google.com/store/apps/details?id=...">
        <div class="form-hint">سيظهر بصفحة التطبيق كأيقونة متجر بلاي قابلة للضغط تفتح هذا الرابط — وليس كرابط نصي كامل</div>
      </div>
      <div class="form-group">
        <label class="form-label">يتطلب أندرويد</label>
        <input class="form-input" id="f-android" type="text" name="android_version" value="<?= h($app['android_version']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">الحجم (MB)</label>
        <input class="form-input" id="f-size" type="text" name="size_mb" value="<?= h($app['size_mb']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">الترخيص</label>
        <input class="form-input" id="f-license" type="text" name="license" value="<?= h($app['license']??'Free') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Package Name</label>
        <input class="form-input" id="f-pkg" type="text" name="package_name" value="<?= h($app['package_name']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">التصنيف</label>
        <select class="form-select" name="category_id">
          <option value="">— اختر —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($app['category_id']??'')==$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">التقييم</label>
        <input class="form-input" id="f-rating" type="number" step="0.1" min="1" max="5" name="rating" value="<?= h($app['rating']??'4.5') ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">ما الجديد في هذا الإصدار</label>
        <textarea class="form-textarea" id="f-whats-new" name="whats_new" rows="3"><?= h($app['whats_new']??'') ?></textarea>
      </div>
    </div>
  </div>

  <!-- ── Media ── -->
  <div class="panel">
    <h2>الصور والملفات</h2>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">أيقونة التطبيق</label>
        <?php if (!empty($app['icon_path'])): ?>
          <img id="icon-preview" src="<?= h($app['icon_path']) ?>" style="width:64px;height:64px;border-radius:14px;object-fit:cover;margin-bottom:8px;border:1px solid var(--border-c)">
        <?php else: ?>
          <img id="icon-preview" src="" style="display:none;width:64px;height:64px;border-radius:14px;object-fit:cover;margin-bottom:8px">
        <?php endif; ?>
        <label class="upload-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
          اختر أيقونة (مربّعة يفضّل)
          <input type="file" name="icon" accept="image/*" data-preview="icon-preview" hidden>
        </label>
      </div>
      <div class="form-group">
        <label class="form-label">صور التطبيق (Screenshots)</label>
        <label class="upload-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
          اختر صور متعددة
          <input type="file" name="screenshots[]" accept="image/*" multiple hidden>
        </label>
        <div class="form-hint">يمكنك رفع عدة صور مرة واحدة — سيتم ضغطها تلقائياً لـ WebP</div>
      </div>
    </div>
    <?php
    $shots = json_decode($app['screenshots'] ?? '[]', true) ?: [];
    if ($shots):
    ?>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
      <?php foreach ($shots as $s): ?>
        <img src="<?= h($s) ?>" style="width:70px;height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--border-c)">
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Download Links ── -->
  <div class="panel">
    <h2>روابط التحميل</h2>
    <div class="form-group" style="margin-bottom:12px">
      <label class="form-label">رابط التحميل الرئيسي</label>
      <input class="form-input" type="text" name="download_url" value="<?= h($app['download_url']??'') ?>" placeholder="https://...">
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">مرآة 2 (اختياري)</label>
        <input class="form-input" type="text" name="mirror2_url" value="<?= h($app['mirror2_url']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">مرآة 3 (اختياري)</label>
        <input class="form-input" type="text" name="mirror3_url" value="<?= h($app['mirror3_url']??'') ?>">
      </div>
    </div>
  </div>

  <!-- ── Dynamic Lists ── -->
  <div class="panel">
    <h2>المميزات</h2>
    <div class="dynamic-list" id="feat-list"></div>
    <button type="button" class="add-item-btn">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14m-7-7h14"/></svg>
      إضافة ميزة
    </button>
  </div>

  <div class="panel">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div>
        <h2>الإيجابيات</h2>
        <div class="dynamic-list" id="pros-list"></div>
        <button type="button" class="add-item-btn" style="margin-top:6px">+ إيجابية</button>
      </div>
      <div>
        <h2>السلبيات</h2>
        <div class="dynamic-list" id="cons-list"></div>
        <button type="button" class="add-item-btn" style="margin-top:6px">+ سلبية</button>
      </div>
    </div>
  </div>

  <div class="panel">
    <h2>خطوات التثبيت</h2>
    <div class="dynamic-list" id="steps-list"></div>
    <button type="button" class="add-item-btn">+ خطوة</button>
  </div>

  <div class="panel">
    <h2>الأسئلة الشائعة (FAQ)</h2>
    <div class="dynamic-list" id="faq-list"></div>
    <button type="button" class="add-item-btn">+ سؤال وجواب</button>
  </div>

  <div class="panel">
    <h2>محتوى إضافي (يُولَّد تلقائياً لكل تطبيق)</h2>
    <p style="color:var(--muted);font-size:12px;margin-bottom:16px">هذه الحقول تُنشر في أسفل صفحة التطبيق لملء المحتوى ورفع جودة SEO — اضغط "توليد" لكل قسم بالذكاء الاصطناعي، ثم عدّل حسب الحاجة.</p>

    <div class="form-group full" style="margin-bottom:18px">
      <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
        <span>ماذا يقدّم هذا التطبيق؟</span>
        <button type="button" class="add-item-btn btn-gen-legal" data-type="offers" style="margin:0;padding:6px 14px;font-size:12px">توليد</button>
      </label>
      <textarea class="form-textarea" id="f-offers" name="offers_text" rows="3" placeholder="فقرة تسويقية تلخص أبرز ما يقدمه التطبيق..."><?= h($app['offers_text'] ?? '') ?></textarea>
    </div>

    <div class="form-group full" style="margin-bottom:18px">
      <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
        <span>سياسة الخصوصية</span>
        <button type="button" class="add-item-btn btn-gen-legal" data-type="privacy" style="margin:0;padding:6px 14px;font-size:12px">توليد</button>
      </label>
      <textarea class="form-textarea" id="f-privacy" name="privacy_policy" rows="5"><?= h($app['privacy_policy']??'') ?></textarea>
    </div>

    <div class="form-group full">
      <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
        <span>شروط الاستخدام</span>
        <button type="button" class="add-item-btn btn-gen-legal" data-type="terms" style="margin:0;padding:6px 14px;font-size:12px">توليد</button>
      </label>
      <textarea class="form-textarea" id="f-terms" name="terms_content" rows="5"><?= h($app['terms_content']??'') ?></textarea>
    </div>
    <div class="ai-status" id="legal-gen-status"></div>
  </div>

  <!-- ── Publish ── -->
  <div class="panel">
    <h2>حالة النشر</h2>
    <div style="display:flex;gap:12px;align-items:center">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
        <input type="radio" name="status" value="published" <?= ($app['status']??'published')!=='draft'?'checked':'' ?>>
        <span style="color:var(--success)">نشر فوري (سيظهر في الموقع مباشرة)</span>
      </label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
        <input type="radio" name="status" value="draft" <?= ($app['status']??'')==='draft'?'checked':'' ?>>
        <span style="color:var(--muted)">مسودة (لن يظهر في الموقع)</span>
      </label>
    </div>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <input type="checkbox" name="needs_update" value="1" <?= !empty($app['needs_update'])?'checked':'' ?>>
      <span style="color:#fbbf24">وضع علامة "يحتاج تحديث" — سيظهر في قسم خاص بالداشبورد لمتابعة تحديثه لاحقاً (الإصدار الحالي يبقى منشوراً)</span>
    </label>
  </div>

  <button type="submit" class="btn-save" style="margin-bottom:40px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
    <?= $isEdit ? 'حفظ التعديلات' : 'إضافة التطبيق' ?>
  </button>
</form>

<script>
window.EXISTING_DATA = <?= json_encode([
    'features' => $feat, 'pros' => $pros, 'cons' => $cons,
    'install_steps' => $steps, 'faq' => $faqArr,
], JSON_UNESCAPED_UNICODE) ?>;
</script>

<?php
/* ─────────────── CATEGORIES ─────────────── */
elseif ($page === 'categories'):
  $cats = $pdo->query("SELECT c.*,(SELECT COUNT(*) FROM apps WHERE category_id=c.id) AS cnt FROM categories c ORDER BY c.sort_order,c.name")->fetchAll();
?>

<div class="admin-header"><h1>التصنيفات</h1></div>

<div class="panel">
  <form method="post" action="admin.php?page=categories" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px">
    <?= csrf_field() ?>
    <div class="form-group" style="flex:1;margin-bottom:0">
      <label class="form-label">اسم التصنيف الجديد</label>
      <input class="form-input" type="text" name="name" required placeholder="مثال: تعليم">
    </div>
    <div class="form-group" style="width:100px;margin-bottom:0">
      <label class="form-label">الترتيب</label>
      <input class="form-input" type="number" name="sort_order" value="0">
    </div>
    <button type="submit" class="btn-save">إضافة</button>
  </form>

  <table class="admin-table">
    <thead><tr><th>الاسم</th><th>Slug</th><th>التطبيقات</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($cats as $c): ?>
    <tr>
      <td style="font-weight:700"><?= h($c['name']) ?></td>
      <td style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= h($c['slug']) ?></td>
      <td style="font-family:var(--f-mono)"><?= $c['cnt'] ?></td>
      <td>
        <a href="admin.php?page=categories&del_cat=<?= $c['id'] ?>&t=<?= csrf_token() ?>"
           class="btn-del" data-confirm="حذف التصنيف «<?= h($c['name']) ?>»؟">حذف</a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
/* ─────────────── CONNECTION TEST ─────────────── */
elseif ($page === 'connection'): ?>

<div class="admin-header"><h1>اختبار الاتصال وحل المشاكل</h1></div>

<div class="panel">
  <p style="color:var(--muted);font-size:13px;margin-bottom:16px">
    اضغط الزر لفحص قاعدة البيانات، الصلاحيات، ومفاتيح OpenRouter — وسيتم تجربة كل موديل مجاني متاح تلقائياً حتى ينجح الاتصال.
  </p>
  <button type="button" id="btn-test-connection" class="btn-ai" style="width:100%;justify-content:center;font-size:15px;padding:16px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    اختبار الاتصال وحل كافة المشاكل
  </button>
  <div id="connection-results" style="margin-top:20px;display:flex;flex-direction:column;gap:10px"></div>
</div>

<?php
/* ─────────────── DATABASE MANAGEMENT ─────────────── */
elseif ($page === 'database'):
  $tablesInfo = [];
  try {
      $tbls = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
      foreach (['admins','categories','apps','settings'] as $t) {
          $exists = in_array($t, $tbls, true);
          $count = $exists ? (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() : 0;
          $tablesInfo[] = ['name'=>$t,'exists'=>$exists,'count'=>$count];
      }
  } catch (Throwable $e) {}
?>

<div class="admin-header"><h1>قاعدة البيانات</h1></div>

<div class="panel">
  <p style="color:var(--muted);font-size:13px;margin-bottom:16px">
    لا حاجة لاستيراد أي ملف SQL يدوياً. الموقع يقوم تلقائياً بإنشاء وتصحيح كل الجداول اللازمة بمجرد الاتصال بقاعدة البيانات، وأي محتوى تنشره يُحفظ فيها مباشرة.
  </p>
  <table class="admin-table">
    <thead><tr><th>الجدول</th><th>الحالة</th><th>عدد السجلات</th></tr></thead>
    <tbody>
    <?php foreach ($tablesInfo as $t): ?>
      <tr>
        <td style="font-family:var(--f-mono)"><?= h($t['name']) ?></td>
        <td><span class="status-badge <?= $t['exists']?'status-published':'status-draft' ?>"><?= $t['exists']?'موجود':'ناقص' ?></span></td>
        <td style="font-family:var(--f-mono)"><?= number_format($t['count']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <button type="button" id="btn-fix-db" class="btn-save" style="margin-top:16px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6c0-1.1 3.6-2 8-2s8 .9 8 2-3.6 2-8 2-8-.9-8-2zm0 0v12c0 1.1 3.6 2 8 2s8-.9 8-2V6M4 12c0 1.1 3.6 2 8 2s8-.9 8-2"/></svg>
    فحص وإصلاح الجداول الآن
  </button>
  <div id="db-fix-result" style="margin-top:12px"></div>
</div>

<?php
/* ─────────────── SETTINGS ─────────────── */
elseif ($page === 'settings'): ?>

<div class="admin-header"><h1>الإعدادات</h1></div>

<form method="post" action="admin.php?page=settings">
  <?= csrf_field() ?>
  <div class="panel">
    <h2>الذكاء الاصطناعي (OpenRouter)</h2>
    <div class="form-group">
      <label class="form-label">مفتاح/مفاتيح OpenRouter API (اختياري إضافة أكثر من مفتاح)</label>
      <textarea class="form-textarea" name="openrouter_key" rows="3" placeholder="sk-or-v1-...&#10;sk-or-v1-... (مفتاح ثاني اختياري)"><?= h(get_cfg($pdo,'openrouter_key')) ?></textarea>
      <div class="form-hint">
        ضع مفتاحاً واحداً أو أكثر (كل مفتاح بسطر منفصل أو مفصول بفاصلة) — سيتم التبديل بينها تلقائياً عند فشل أي مفتاح.
        احصل على مفتاح مجاني من <a href="https://openrouter.ai/keys" target="_blank" style="color:var(--cyan)">openrouter.ai/keys</a>
      </div>
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">الموديل الأساسي</label>
        <select class="form-select" id="sel-primary-model" name="openrouter_model">
          <option value="<?= h(get_cfg($pdo,'openrouter_model','openrouter/free')) ?>" selected><?= h(get_cfg($pdo,'openrouter_model','openrouter/free')) ?></option>
        </select>
        <div class="form-hint" id="models-hint">جاري تحميل قائمة الموديلات المجانية...</div>
      </div>
      <div class="form-group">
        <label class="form-label">موديل احتياطي (Fallback)</label>
        <select class="form-select" id="sel-fallback-model" name="openrouter_fallback">
          <option value="<?= h(get_cfg($pdo,'openrouter_fallback','meta-llama/llama-3.3-70b-instruct:free')) ?>" selected><?= h(get_cfg($pdo,'openrouter_fallback','meta-llama/llama-3.3-70b-instruct:free')) ?></option>
        </select>
      </div>
    </div>
    <div class="form-group" style="margin-top:6px">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--white)">
        <input type="checkbox" name="openrouter_auto_rotate" value="1" <?= get_cfg($pdo,'openrouter_auto_rotate','1')==='1'?'checked':'' ?>>
        التبديل التلقائي بين كل الموديلات المجانية حتى ينجح الاتصال
      </label>
    </div>
    <button type="button" id="btn-test-connection-inline" class="btn-ai" style="margin-top:14px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      اختبار الاتصال الآن
    </button>
    <div id="inline-test-result" style="margin-top:12px"></div>
  </div>

  <div class="panel">
    <h2>إعدادات الإعلانات</h2>
    <div class="form-group">
      <label class="form-label">MoneyTag Zone ID</label>
      <input class="form-input" type="text" name="moneytag_zone" value="<?= h(get_cfg($pdo,'moneytag_zone','258058')) ?>">
      <div class="form-hint">الرمز الحالي المفعّل: 258058 على الرابط quge5.com/88/tag.min.js</div>
    </div>
  </div>

  <button type="submit" class="btn-save">حفظ الإعدادات</button>
</form>

<?php endif; ?>

</div><!-- /admin-main -->
</div><!-- /admin-wrap -->

<script src="assets/js/admin.js"></script>
</body>
</html>
