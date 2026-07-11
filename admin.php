<?php
require_once __DIR__ . '/config.php';
admin_ip_check($pdo);

// AI-generation AJAX calls can legitimately take tens of seconds (external
// OpenRouter requests). PHP's default file-based session handler holds an
// exclusive lock on the session file for the whole request — without
// releasing it here, one long-running generation blocks every other
// request from the SAME logged-in admin (e.g. a second browser tab, or the
// page trying to poll status) until it finishes. None of the AJAX handlers
// below write to $_SESSION after this point, so closing it early is safe.
if (isset($_GET['ajax']) && is_admin()) {
    session_write_close();
}

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

    $seoStandards = seo_prompt_standards();
    $prompt = <<<P
أنت خبير تسويق تطبيقات أندرويد وكاتب محتوى SEO محترف متخصص في متاجر التطبيقات العربية. التطبيق: "{$name}"

{$seoStandards}

- long_description: وصف أصلي احترافي 600-900 كلمة على الأقل (وليس فقرة قصيرة)، عدة فقرات تغطي: نظرة عامة على التطبيق، أبرز الميزات بالتفصيل، لمن يناسب هذا التطبيق، وأسلوب طبيعي يخدم SEO دون حشو كلمات. (يمكن للأدمن توسيعه لاحقاً حتى 1500-3000 كلمة بزر "متابعة الكتابة").

أعد JSON صالح فقط بدون أي نص آخر أو Markdown:
{
  "name":"الاسم الرسمي",
  "seo_title":"",
  "meta_description":"",
  "keywords":"",
  "short_description":"جملة أو جملتين",
  "long_description":"",
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
   AJAX: Repair legacy invalid-UTF-8 bytes already stored in the DB
   (older rows saved before clean_utf8() was applied at save time can
   still contain bad bytes, which is what caused long descriptions to
   render as a blank gap). One-time, safe to re-run, only writes rows
   that actually changed.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'repair_encoding' && is_admin()) {
    header('Content-Type: application/json');
    $textCols = ['name','seo_title','meta_description','keywords','short_description','long_description',
        'privacy_policy','terms_content','offers_text','whats_new','developer'];
    $fixed = 0; $scanned = 0;
    $rows = $pdo->query("SELECT id," . implode(',', $textCols) . " FROM apps")->fetchAll();
    foreach ($rows as $row) {
        $scanned++;
        $set = []; $vals = [];
        foreach ($textCols as $col) {
            $clean = clean_utf8($row[$col] ?? '');
            if ($clean !== ($row[$col] ?? '')) { $set[] = "$col=?"; $vals[] = $clean; }
        }
        if ($set) {
            $vals[] = $row['id'];
            $pdo->prepare("UPDATE apps SET " . implode(',', $set) . " WHERE id=?")->execute($vals);
            $fixed++;
        }
    }
    $catRows = $pdo->query("SELECT id,description FROM categories")->fetchAll();
    foreach ($catRows as $row) {
        $clean = clean_utf8($row['description'] ?? '');
        if ($clean !== ($row['description'] ?? '')) {
            $pdo->prepare("UPDATE categories SET description=? WHERE id=?")->execute([$clean, $row['id']]);
            $fixed++;
        }
    }
    bump_cache_version($pdo);
    echo json_encode(['success'=>true,'message'=>"تم فحص {$scanned} تطبيق، وإصلاح ترميز {$fixed} صف."], JSON_UNESCAPED_UNICODE);
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

    $seoStandards = seo_prompt_standards();
    $prompt = <<<P
التطبيق: "{$app['name']}"
الوصف الحالي: "{$app['short_description']}"

{$seoStandards}

أعد JSON صالح فقط بدون أي نص إضافي:
{"seo_title":"","meta_description":"","keywords":""}
P;
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $data = ai_extract_json($r['content']);
    if (!$data) { echo json_encode(['success'=>false,'error'=>'رد غير صالح من الموديل']); exit; }
    $data = clean_utf8_deep($data);

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

    $meta = fetch_playstore_meta($src);
    if (!$meta) {
        echo json_encode(['success'=>false,'error'=>'لم يتم العثور على بيانات في الصفحة — قد تكون Google تمنع الوصول الآلي من هذا الاستضافة']); exit;
    }

    echo json_encode(array_merge($meta, [
        'success' => true,
        'note' => 'تم استيراد العنوان والوصف والأيقونة فقط. رابط التحميل المباشر غير متاح من Google Play — أضفه يدوياً أو استخدم توليد AI لباقي الحقول.',
    ]), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: AI-generate an app icon (best effort — see
   ai_generate_image() in config.php for why this can fail
   even with a valid key: free image models are scarce).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_icon_ai' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['name'] ?? '');
    $desc  = trim($input['description'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم التطبيق مطلوب']); exit; }

    $prompt = "Generate a professional, modern, minimalist square Android app icon (no text, no watermark, centered symbol, flat/gradient style, 1024x1024) for an app called \"$name\"" . ($desc ? (" — " . mb_substr($desc, 0, 200)) : '') . ".";
    $r = ai_generate_image($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }

    $tmp = tempnam(sys_get_temp_dir(), 'aiicn');
    file_put_contents($tmp, $r['bin']);
    $fakeFile = ['tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($r['bin'])];
    $path = process_icon($fakeFile, slugify($name));
    @unlink($tmp);

    if (!$path) { echo json_encode(['success'=>false,'error'=>'تم توليد الصورة لكن تعذّرت معالجتها كأيقونة صالحة']); exit; }
    echo json_encode(['success'=>true,'path'=>$path,'url'=>url($path)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: AI-generate an app screenshot (best effort, same
   caveats as the icon endpoint above).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_screenshot_ai' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['name'] ?? '');
    $desc  = trim($input['description'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم التطبيق مطلوب']); exit; }

    $prompt = "Generate a realistic Android app screenshot mockup (portrait 9:16, clean modern mobile UI, no watermark, no device frame) plausibly showing the in-app interface of an app called \"$name\"" . ($desc ? (" — " . mb_substr($desc, 0, 200)) : '') . ".";
    $r = ai_generate_image($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }

    $dir = UPLOAD_PATH . '/screenshots';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $tmp = tempnam(sys_get_temp_dir(), 'aiss');
    file_put_contents($tmp, $r['bin']);
    $info = @getimagesize($tmp);
    if (!$info) { @unlink($tmp); echo json_encode(['success'=>false,'error'=>'تعذرت قراءة الصورة المولّدة']); exit; }

    $fname = slugify($name) . '-ai-' . substr(md5(uniqid()), 0, 6) . '.webp';
    $dest  = "$dir/$fname";
    $ok = compress_image_to($tmp, $info['mime'], $dest, 1080, 2000, 82);
    @unlink($tmp);

    if (!$ok) { echo json_encode(['success'=>false,'error'=>'فشل ضغط الصورة المولّدة']); exit; }
    $relPath = "uploads/screenshots/$fname";
    echo json_encode(['success'=>true,'path'=>$relPath,'url'=>url($relPath)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate an SEO description for a category
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_cat_description' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['name'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم التصنيف مطلوب']); exit; }

    $prompt = "اكتب فقرة تعريفية احترافية بطول 80-150 كلمة لصفحة تصنيف \"{$name}\" على متجر تطبيقات أندرويد عربي، تشرح للزائر ما يجده في هذا التصنيف بأسلوب طبيعي يخدم SEO. فقرة نصية عادية فقط بدون عناوين أو Markdown، وبدون مقدمات مثل \"بالتأكيد\".";
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $content = trim(preg_replace('/^```\w*|```$/m', '', $r['content']));
    echo json_encode(['success'=>true,'content'=>$content], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate 3 short related articles for an app —
   internal linking back to its download page (SEO + AdSense).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_articles' && is_admin()) {
    header('Content-Type: application/json');
    $appId = (int)($_GET['app_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id,name,short_description FROM apps WHERE id=?");
    $stmt->execute([$appId]);
    $a = $stmt->fetch();
    if (!$a) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }

    $prompt = <<<P
أنت كاتب محتوى عربي محترف لموقع تحميل تطبيقات أندرويد. اكتب 3 مقالات قصيرة أصلية ومفيدة (وليست إعلانية مبالغاً فيها)
عن تطبيق "{$a['name']}" ({$a['short_description']}), بأنواع مختلفة، مثلاً: شرح كيفية استخدام التطبيق للمبتدئين، مقارنة
بينه وبين بدائل مشهورة في نفس المجال، أو نصائح وحيل لاستخدامه بفعالية. كل مقال بطول 250-450 كلمة، فقرات طبيعية بدون
Markdown، تنتهي كل مقالة بجملة طبيعية تدعو القارئ لتحميل "{$a['name']}" (دون رابط، الرابط سيُضاف تلقائياً).
أعد JSON فقط بالشكل التالي، بدون أي نص خارج JSON:
{"articles":[{"title":"","body":""},{"title":"","body":""},{"title":"","body":""}]}
P;
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $data = ai_extract_json($r['content']);
    $data = clean_utf8_deep($data ?? []);
    $articles = $data['articles'] ?? [];
    if (!$articles) { echo json_encode(['success'=>false,'error'=>'رد الذكاء الاصطناعي لم يكن بالشكل المتوقع']); exit; }

    $created = 0;
    foreach ($articles as $art) {
        $title = trim($art['title'] ?? '');
        $body  = trim($art['body'] ?? '');
        if (!$title || !$body) continue;
        $slug = unique_article_slug($pdo, $a['name'] . '-' . $title);
        $pdo->prepare("INSERT INTO app_articles (app_id,title,slug,body) VALUES (?,?,?,?)")
            ->execute([$appId, $title, $slug, $body]);
        $created++;
    }
    echo json_encode(['success'=>true,'created'=>$created], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: AI admin assistant — a safe, whitelisted-actions
   console. The admin types a request in plain language;
   the model maps it to ONE of a fixed set of actions below
   and PHP executes it through the exact same functions the
   rest of admin.php already uses. There is no action that
   writes to the filesystem outside uploads/, executes shell
   commands, or touches PHP/template files — by design, this
   never becomes a live remote-code-execution surface even if
   the admin session were ever compromised.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'assistant' && is_admin()) {
    header('Content-Type: application/json');
    $input   = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    if (!$message) { echo json_encode(['success'=>false,'error'=>'اكتب طلباً أولاً']); exit; }

    $recentApps = $pdo->query("SELECT id,name,status FROM apps ORDER BY id DESC LIMIT 40")->fetchAll();
    $appsList = implode("\n", array_map(fn($a) => "#{$a['id']} {$a['name']} ({$a['status']})", $recentApps));
    $allowedSettingKeys = ['moneytag_zone','openrouter_model','openrouter_fallback','openrouter_auto_rotate','openrouter_image_model'];

    $prompt = <<<P
أنت مساعد إدارة داخل لوحة تحكم متجر تطبيقات "yassota". لديك قدرة على تنفيذ إجراءات محدّدة فقط عبر إرجاع JSON — لا تنفّذ أي كود، ولا تكتب ملفات، فقط تختار إجراءً من القائمة المسموحة التالية:

- "chat": للرد على سؤال أو توضيح بدون أي تنفيذ. params: {}
- "create_app_draft": ينشئ تطبيقاً جديداً كمسودة بمحتوى مولّد بالذكاء الاصطناعي (بدون رابط تحميل — يضيفه الأدمن لاحقاً). params: {"name": "اسم التطبيق"}
- "regenerate_seo_one": يعيد توليد عنوان/وصف/كلمات SEO لتطبيق محدد. params: {"app_id": رقم}
- "regenerate_seo_all": يعيد توليد SEO لكل التطبيقات المنشورة. params: {}
- "generate_icon": يولّد أيقونة بالذكاء الاصطناعي لتطبيق محدد ويحفظها له مباشرة. params: {"app_id": رقم}
- "update_setting": يغيّر إعداداً غير حساس. المفاتيح المسموحة فقط: moneytag_zone, openrouter_model, openrouter_fallback, openrouter_auto_rotate, openrouter_image_model. params: {"key": "...", "value": "..."}
- "list_apps": يسرد أحدث التطبيقات. params: {}

قائمة أحدث التطبيقات (id، الاسم، الحالة):
{$appsList}

طلب الأدمن: "{$message}"

أعد JSON فقط بدون أي نص إضافي أو Markdown بهذا الشكل بالضبط:
{"action":"...","params":{...},"reply":"رد قصير بالعربية يشرح ماذا ستفعل أو رد مباشر إن كان action=chat"}
إذا لم يطابق الطلب أي إجراء مسموح أو كان غامضاً، استخدم action="chat" واشرح ذلك في reply.
P;

    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $decision = ai_extract_json($r['content']);
    if (!$decision || empty($decision['action'])) {
        echo json_encode(['success'=>true,'reply'=>trim($r['content']),'action'=>'chat']); exit;
    }

    $action = $decision['action'];
    $params = $decision['params'] ?? [];
    $reply  = trim($decision['reply'] ?? '');
    $result = null;

    switch ($action) {
        case 'create_app_draft':
            $name = trim($params['name'] ?? '');
            if (!$name) { $result = 'لم يتم تحديد اسم التطبيق'; break; }
            $genPrompt = "أنت خبير تسويق تطبيقات أندرويد وكاتب محتوى SEO محترف. التطبيق: \"{$name}\"\n\n" . seo_prompt_standards() .
                "\n\nأعد JSON صالح فقط بدون أي نص آخر:\n{\"seo_title\":\"\",\"meta_description\":\"\",\"keywords\":\"\",\"short_description\":\"\",\"long_description\":\"\",\"developer\":\"\",\"version\":\"1.0.0\",\"android_version\":\"\",\"size_mb\":\"\",\"rating\":4.5,\"whats_new\":\"\"}";
            $gr = ai_text($pdo, $genPrompt);
            $data = $gr['ok'] ? ai_extract_json($gr['content']) : null;
            $data = clean_utf8_deep($data ?? []);
            $slug = unique_slug($pdo, $name);
            $pdo->prepare("INSERT INTO apps (name,slug,seo_title,meta_description,keywords,short_description,long_description,developer,version,android_version,size_mb,rating,whats_new,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")
                ->execute([
                    $name, $slug,
                    trim($data['seo_title'] ?? ''), trim($data['meta_description'] ?? ''), trim($data['keywords'] ?? ''),
                    trim($data['short_description'] ?? ''), trim($data['long_description'] ?? ''),
                    trim($data['developer'] ?? ''), trim($data['version'] ?? '1.0.0'), trim($data['android_version'] ?? ''),
                    trim($data['size_mb'] ?? ''), (float)($data['rating'] ?? 4.5), trim($data['whats_new'] ?? ''),
                ]);
            $newId = (int)$pdo->lastInsertId();
            $result = "تم إنشاء مسودة #{$newId} — {$name}. أضف رابط التحميل والأيقونة ثم انشرها من صفحة التعديل.";
            break;

        case 'regenerate_seo_one':
            $id = (int)($params['app_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id,name,short_description FROM apps WHERE id=?");
            $stmt->execute([$id]); $a = $stmt->fetch();
            if (!$a) { $result = "لم يتم العثور على تطبيق برقم #{$id}"; break; }
            $seoPrompt = "التطبيق: \"{$a['name']}\" الوصف الحالي: \"{$a['short_description']}\"\n\n" . seo_prompt_standards() .
                "\n\nأعد JSON فقط: {\"seo_title\":\"\",\"meta_description\":\"\",\"keywords\":\"\"}";
            $sr = ai_text($pdo, $seoPrompt);
            $sd = $sr['ok'] ? ai_extract_json($sr['content']) : null;
            if (!$sd) { $result = 'فشل التوليد'; break; }
            $sd = clean_utf8_deep($sd);
            $pdo->prepare("UPDATE apps SET seo_title=?, meta_description=?, keywords=? WHERE id=?")
                ->execute([trim($sd['seo_title']??''), trim($sd['meta_description']??''), trim($sd['keywords']??''), $id]);
            $result = "تم تحديث SEO للتطبيق #{$id} — {$a['name']}";
            break;

        case 'regenerate_seo_all':
            // Capped per call — looping over every published app in one PHP
            // request (with each AI call taking real seconds) is exactly the
            // kind of single request that can hold a PHP worker for minutes
            // and make the rest of the site feel slow while it runs. Ask
            // again to continue with the next batch instead.
            $batchLimit = 15;
            $allIds = $pdo->query("SELECT id FROM apps WHERE status='published' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
            $ids = array_slice($allIds, 0, $batchLimit);
            $done = 0;
            foreach ($ids as $id) {
                $stmt = $pdo->prepare("SELECT name,short_description FROM apps WHERE id=?");
                $stmt->execute([$id]); $a = $stmt->fetch();
                if (!$a) continue;
                $sr = ai_text($pdo, "التطبيق: \"{$a['name']}\"\n\n" . seo_prompt_standards() . "\n\nأعد JSON فقط: {\"seo_title\":\"\",\"meta_description\":\"\",\"keywords\":\"\"}");
                $sd = $sr['ok'] ? ai_extract_json($sr['content']) : null;
                if ($sd) {
                    $sd = clean_utf8_deep($sd);
                    $pdo->prepare("UPDATE apps SET seo_title=?, meta_description=?, keywords=? WHERE id=?")
                        ->execute([trim($sd['seo_title']??''), trim($sd['meta_description']??''), trim($sd['keywords']??''), $id]);
                    $done++;
                }
            }
            $remaining = count($allIds) - count($ids);
            $result = "تم تحديث SEO لـ {$done} من أصل " . count($ids) . " (دفعة واحدة من {$batchLimit} كحد أقصى)."
                . ($remaining > 0 ? " تبقّى {$remaining} تطبيق — اكتب نفس الطلب مرة أخرى لمتابعة الدفعة التالية." : " تم الانتهاء من كل التطبيقات المنشورة.");
            break;

        case 'generate_icon':
            $id = (int)($params['app_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id,name,short_description FROM apps WHERE id=?");
            $stmt->execute([$id]); $a = $stmt->fetch();
            if (!$a) { $result = "لم يتم العثور على تطبيق برقم #{$id}"; break; }
            $ir = ai_generate_image($pdo, "Generate a professional, modern, minimalist square Android app icon (no text, no watermark, centered symbol, flat/gradient style, 1024x1024) for an app called \"{$a['name']}\".");
            if (!$ir['ok']) { $result = "فشل توليد الأيقونة: {$ir['error']}"; break; }
            $tmp = tempnam(sys_get_temp_dir(), 'aiicn');
            file_put_contents($tmp, $ir['bin']);
            $path = process_icon(['tmp_name'=>$tmp,'error'=>UPLOAD_ERR_OK,'size'=>strlen($ir['bin'])], slugify($a['name']));
            @unlink($tmp);
            if (!$path) { $result = 'تم توليد الصورة لكن تعذّرت معالجتها'; break; }
            $pdo->prepare("UPDATE apps SET icon_path=? WHERE id=?")->execute([$path, $id]);
            bump_cache_version($pdo);
            $result = "تم توليد وحفظ أيقونة جديدة للتطبيق #{$id} — {$a['name']}";
            break;

        case 'update_setting':
            $key = trim($params['key'] ?? '');
            $val = trim($params['value'] ?? '');
            if (!in_array($key, $allowedSettingKeys, true)) { $result = "الإعداد \"{$key}\" غير مسموح بتغييره من هنا"; break; }
            set_cfg($pdo, $key, $val);
            $result = "تم تحديث الإعداد {$key}";
            break;

        case 'list_apps':
            $result = $appsList ?: 'لا توجد تطبيقات بعد';
            break;

        case 'chat':
        default:
            $result = null;
            break;
    }

    echo json_encode(['success'=>true,'action'=>$action,'reply'=>$reply,'result'=>$result], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Suggest N trending app/game names via AI
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'suggest_trending' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $count = max(1, min(30, (int)($input['count'] ?? 10)));
    $type  = in_array($input['type'] ?? 'apps', ['apps','games','mixed'], true) ? $input['type'] : 'apps';
    $hint  = trim($input['hint'] ?? '');

    $typeLabel = ['apps' => 'تطبيقات', 'games' => 'ألعاب', 'mixed' => 'تطبيقات وألعاب'][$type];
    $prompt = "اقترح {$count} اسم {$typeLabel} أندرويد حقيقية من الأكثر بحثاً وتحميلاً حالياً (رسمية أو نسخ معدّلة/مود شائعة). " .
        ($hint ? "مجال/تفضيل إضافي: {$hint}. " : '') .
        "أعد أسماء دقيقة وقابلة للبحث في متجر التطبيقات، بدون تكرار.\n" .
        "أعد JSON فقط بدون أي نص إضافي: {\"names\":[\"اسم 1\",\"اسم 2\"]}";

    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $data = ai_extract_json($r['content']);
    $names = is_array($data['names'] ?? null) ? array_values(array_filter(array_map('trim', $data['names']))) : [];
    if (!$names) { echo json_encode(['success'=>false,'error'=>'لم يُرجع الموديل قائمة أسماء صالحة، حاول مجدداً']); exit; }

    echo json_encode(['success'=>true,'names'=>$names], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Full bulk-create pipeline for ONE app/game name —
   AI content, best-effort Play Store search/import, AI icon
   fallback, insert. Called once per name from the client so
   a 30-item batch can't time out a single request. Apps with
   no download link land as drafts via the existing forced-
   draft rule (there is no draft/publish distinction to set
   here — the app simply has no download_url yet).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'bulk_create_one' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $type = in_array($input['type'] ?? 'apps', ['apps','games'], true) ? $input['type'] : 'apps';
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم مطلوب']); exit; }

    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) { echo json_encode(['success'=>false,'error'=>'لم يتم إضافة مفتاح OpenRouter بعد.']); exit; }
    $models = build_model_rotation($pdo);

    $seoStandards = seo_prompt_standards();
    $prompt = <<<P
أنت خبير تسويق تطبيقات أندرويد وكاتب محتوى SEO محترف متخصص في متاجر التطبيقات العربية. التطبيق: "{$name}"

{$seoStandards}

- long_description: وصف أصلي احترافي 600-900 كلمة على الأقل (وليس فقرة قصيرة)، عدة فقرات تغطي: نظرة عامة على التطبيق، أبرز الميزات بالتفصيل، لمن يناسب هذا التطبيق، وأسلوب طبيعي يخدم SEO دون حشو كلمات. (يمكن للأدمن توسيعه لاحقاً حتى 1500-3000 كلمة بزر "متابعة الكتابة").

أعد JSON صالح فقط بدون أي نص آخر أو Markdown:
{
  "name":"الاسم الرسمي",
  "seo_title":"",
  "meta_description":"",
  "keywords":"",
  "short_description":"جملة أو جملتين",
  "long_description":"",
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
    if (!$result['ok']) { echo json_encode(['success'=>false,'error'=>'فشل توليد المحتوى بالذكاء الاصطناعي']); exit; }
    $data = ai_extract_json($result['content']);
    if (!$data) { echo json_encode(['success'=>false,'error'=>'رد الذكاء الاصطناعي لم يكن JSON صالحاً']); exit; }
    $data = clean_utf8_deep($data);

    $finalName = trim($data['name'] ?? $name) ?: $name;
    $slug = unique_slug($pdo, $finalName);

    // Best-effort Play Store search + import — a miss here is normal, not an error.
    $playstoreUrl = playstore_search($finalName);
    $iconPath = null;
    $playstoreMeta = $playstoreUrl ? fetch_playstore_meta($playstoreUrl) : null;
    if ($playstoreMeta && !empty($playstoreMeta['icon_url'])) {
        $iconPath = import_remote_icon($playstoreMeta['icon_url'], $slug);
    }
    // AI icon fallback if Play Store import didn't yield one.
    if (!$iconPath) {
        $ir = ai_generate_image($pdo, "Generate a professional, modern, minimalist square Android app icon (no text, no watermark, centered symbol, flat/gradient style, 1024x1024) for an app called \"{$finalName}\".");
        if ($ir['ok']) {
            $tmp = tempnam(sys_get_temp_dir(), 'aiicn');
            file_put_contents($tmp, $ir['bin']);
            $iconPath = process_icon(['tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($ir['bin'])], $slug);
            @unlink($tmp);
        }
    }

    $catStmt = $pdo->prepare("SELECT id FROM categories WHERE slug=?");
    $catStmt->execute([$type === 'games' ? 'games' : 'apps']);
    $catId = $catStmt->fetchColumn() ?: null;

    $features     = array_values(array_filter($data['features'] ?? []));
    $pros         = array_values(array_filter($data['pros'] ?? []));
    $cons         = array_values(array_filter($data['cons'] ?? []));
    $installSteps = array_values(array_filter($data['install_steps'] ?? []));
    $faq          = array_is_list($data['faq'] ?? []) ? $data['faq'] : [];

    $pdo->prepare("INSERT INTO apps (name,slug,category_id,developer,version,android_version,size_mb,license,package_name,icon_path,short_description,long_description,features,pros,cons,install_steps,faq,whats_new,playstore_url,rating,seo_title,meta_description,keywords,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")
        ->execute([
            $finalName, $slug, $catId,
            trim($data['developer'] ?? ''), trim($data['version'] ?? ''), trim($data['android_version'] ?? ''),
            trim($data['size_mb'] ?? ''), trim($data['license'] ?? 'Free'), trim($data['package_name'] ?? ''),
            $iconPath,
            trim($data['short_description'] ?? ''), trim($data['long_description'] ?? ''),
            json_encode($features, JSON_UNESCAPED_UNICODE), json_encode($pros, JSON_UNESCAPED_UNICODE),
            json_encode($cons, JSON_UNESCAPED_UNICODE), json_encode($installSteps, JSON_UNESCAPED_UNICODE),
            json_encode($faq, JSON_UNESCAPED_UNICODE), trim($data['whats_new'] ?? ''),
            $playstoreUrl, (float)($data['rating'] ?? 4.5),
            trim($data['seo_title'] ?? ''), trim($data['meta_description'] ?? ''), trim($data['keywords'] ?? ''),
        ]);
    $newId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true, 'id' => $newId, 'name' => $finalName, 'slug' => $slug,
        'has_playstore' => (bool)$playstoreUrl, 'has_icon' => (bool)$iconPath,
        'edit_url' => 'admin.php?page=edit-app&id=' . $newId,
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
    foreach (['openrouter_key','openrouter_model','openrouter_fallback','openrouter_image_model','moneytag_zone','contact_email'] as $k) {
        set_cfg($pdo, $k, trim($_POST[$k] ?? ''));
    }
    set_cfg($pdo, 'openrouter_auto_rotate', isset($_POST['openrouter_auto_rotate']) ? '1' : '0');

    // Optional IP allowlist — auto-include the saving admin's current IP so a save can never lock them out.
    $newAllowlist = trim($_POST['admin_ip_allowlist'] ?? '');
    if ($newAllowlist !== '') {
        $ips = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $newAllowlist)));
        if (!in_array(client_ip(), $ips, true)) $ips[] = client_ip();
        set_cfg($pdo, 'admin_ip_allowlist', implode("\n", array_unique($ips)));
    } else {
        set_cfg($pdo, 'admin_ip_allowlist', '');
    }
    $msg = 'تم حفظ الإعدادات';
}

// ─── Delete app ───
if ($page === 'apps' && isset($_GET['del']) && isset($_GET['t']) &&
    hash_equals($_SESSION['csrf'] ?? '', $_GET['t'])) {
    $pdo->prepare("DELETE FROM apps WHERE id=?")->execute([(int)$_GET['del']]);
    bump_cache_version($pdo);
    header('Location: admin.php?page=apps&msg=deleted'); exit;
}

// ─── Delete a related article ───
if ($page === 'edit-app' && isset($_GET['del_article']) && isset($_GET['t']) &&
    hash_equals($_SESSION['csrf'] ?? '', $_GET['t'])) {
    $pdo->prepare("DELETE FROM app_articles WHERE id=?")->execute([(int)$_GET['del_article']]);
    header('Location: admin.php?page=edit-app&id=' . (int)($_GET['id'] ?? 0)); exit;
}

// ─── Save / Update app ───
if (in_array($page, ['add-app','edit-app']) && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    // Strip any invalid UTF-8 bytes from every submitted field before they
    // ever reach the database — belt-and-suspenders alongside h()'s
    // ENT_SUBSTITUTE, in case the hosting DB is non-strict and would
    // otherwise silently store/mangle bad bytes.
    $_POST = clean_utf8_deep($_POST);
    $isEdit = $page === 'edit-app';
    $appId  = (int)($_POST['app_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');

    if (!$name) { $error = 'اسم التطبيق مطلوب'; goto render; }

    // Slug
    if ($isEdit && $appId) {
        $existing = $pdo->prepare("SELECT slug,icon_path,screenshots,version,download_url,whats_new FROM apps WHERE id=?");
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
        $p = import_remote_icon($_POST['icon_url_import'], $slug);
        if ($p) $iconPath = $p;
    } elseif (!empty($_POST['ai_icon_path']) && preg_match('#^uploads/icons/[A-Za-z0-9_\-\.]+\.webp$#', trim($_POST['ai_icon_path']))) {
        // Icon was generated by AI and already saved server-side — just reference it.
        $iconPath = trim($_POST['ai_icon_path']);
    }

    // Screenshots
    $shots = json_decode($existing['screenshots'] ?? '[]', true) ?: [];
    if (!empty($_FILES['screenshots']['name'][0])) {
        $newShots = process_screenshots($_FILES['screenshots'], $slug);
        $shots = array_merge($shots, $newShots);
    }
    if (!empty($_POST['ai_screenshot_paths']) && is_array($_POST['ai_screenshot_paths'])) {
        // Screenshots generated by AI and already saved server-side — just reference them.
        foreach ($_POST['ai_screenshot_paths'] as $p) {
            $p = trim($p);
            if (preg_match('#^uploads/screenshots/[A-Za-z0-9_\-\.]+\.webp$#', $p)) $shots[] = $p;
        }
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

    // Self-heal a common data-entry mistake: a Play Store URL pasted into the
    // "Play Store version" field (which should only ever hold a short string
    // like "12.8.0") instead of the dedicated Play Store URL field.
    $playStoreVersionIn = trim($_POST['play_store_version'] ?? '');
    $playstoreUrlIn     = trim($_POST['playstore_url'] ?? '');
    if (str_contains($playStoreVersionIn, '://')) {
        if ($playstoreUrlIn === '') $playstoreUrlIn = $playStoreVersionIn;
        $playStoreVersionIn = '';
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
        'play_store_version'=> $playStoreVersionIn,
        'playstore_url'     => $playstoreUrlIn,
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
        // Optionally snapshot the version being replaced into app_versions
        // before overwriting it, so old versions/changelogs stay browsable
        // and downloadable instead of being silently lost on every edit.
        if (!empty($_POST['save_as_new_version']) && $existing) {
            $versionChanged = trim($existing['version'] ?? '') !== trim($d['version'] ?? '')
                || trim($existing['download_url'] ?? '') !== trim($downloadUrl);
            if ($versionChanged && trim($existing['version'] ?? '') !== '') {
                $pdo->prepare("INSERT INTO app_versions (app_id,version,changelog,download_url) VALUES (?,?,?,?)")
                    ->execute([$appId, $existing['version'], $existing['whats_new'], $existing['download_url']]);
            }
        }
        $sets = implode(', ', array_map(fn($k) => "$k=:$k", array_keys($d)));
        $d['id'] = $appId;
        $pdo->prepare("UPDATE apps SET $sets WHERE id=:id")->execute($d);
        bump_cache_version($pdo);
        header('Location: admin.php?page=apps&msg=' . ($forcedDraft ? 'updated_no_link' : 'updated')); exit;
    } else {
        $d['slug'] = $slug;
        $cols = implode(',', array_keys($d));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($d)));
        $pdo->prepare("INSERT INTO apps ($cols) VALUES ($vals)")->execute($d);
        bump_cache_version($pdo);
        header('Location: admin.php?page=apps&msg=' . ($forcedDraft ? 'added_no_link' : 'added')); exit;
    }
}

// ─── Categories ───
if ($page === 'categories') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check() && isset($_POST['cat_id'])) {
        // Updating a category's SEO description
        $pdo->prepare("UPDATE categories SET description=? WHERE id=?")
            ->execute([trim($_POST['description'] ?? ''), (int)$_POST['cat_id']]);
        bump_cache_version($pdo);
        header('Location: admin.php?page=categories&msg=updated'); exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
        $n = trim($_POST['name'] ?? '');
        if ($n) $pdo->prepare("INSERT IGNORE INTO categories (name,slug,sort_order) VALUES(?,?,?)")
            ->execute([$n, slugify($n), (int)($_POST['sort_order']??0)]);
    }
    if (isset($_GET['del_cat']) && isset($_GET['t']) && hash_equals($_SESSION['csrf']??'', $_GET['t'])) {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_GET['del_cat']]);
        header('Location: admin.php?page=categories&msg=deleted'); exit;
    }
}

// ─── Contact messages ───
if ($page === 'messages') {
    if (isset($_GET['del_msg']) && isset($_GET['t']) && hash_equals($_SESSION['csrf']??'', $_GET['t'])) {
        $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['del_msg']]);
        header('Location: admin.php?page=messages&msg=deleted'); exit;
    }
    if (isset($_GET['view'])) {
        $pdo->prepare("UPDATE contact_messages SET status='read' WHERE id=?")->execute([(int)$_GET['view']]);
    }
}

// ─── Comment moderation ───
if ($page === 'comments' && isset($_GET['t']) && hash_equals($_SESSION['csrf']??'', $_GET['t'])) {
    if (isset($_GET['approve'])) {
        $pdo->prepare("UPDATE comments SET status='approved' WHERE id=?")->execute([(int)$_GET['approve']]);
        header('Location: admin.php?page=comments&msg=updated'); exit;
    }
    if (isset($_GET['del_comment'])) {
        $pdo->prepare("DELETE FROM comments WHERE id=?")->execute([(int)$_GET['del_comment']]);
        header('Location: admin.php?page=comments&msg=deleted'); exit;
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
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/admin.css')) ?>">
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
    'bulk-generate' => ['label'=>'توليد تطبيقات رائجة', 'icon'=>'M12 2l2.4 7.2H22l-6 4.6 2.3 7.2-6.3-4.5-6.3 4.5 2.3-7.2-6-4.6h7.6z'],
    'assistant' => ['label'=>'مساعد الذكاء الاصطناعي', 'icon'=>'M9 18h6m-5 3h4M12 3a6 6 0 00-4 10.5c.6.5 1 1.3 1 2.1V16h6v-.4c0-.8.4-1.6 1-2.1A6 6 0 0012 3z'],
    'messages'  => ['label'=>'رسائل التواصل', 'icon'=>'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zm0 2l8 6 8-6'],
    'comments'  => ['label'=>'التعليقات والتقييمات', 'icon'=>'M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z'],
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
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/admin.css')) ?>">
</head>
<body>
<!-- ═══ MOBILE TOPBAR (يظهر فقط على الشاشات الصغيرة) ═══ -->
<div class="admin-mobile-topbar">
  <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="القائمة" aria-expanded="false">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2.1"/><circle cx="12" cy="12" r="2.1"/><circle cx="12" cy="19" r="2.1"/></svg>
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
    <a href="/" target="_blank" style="margin-top:auto">
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
        <a href="<?= h(app_url($a['slug'])) ?>" target="_blank" class="btn-view">
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
<div class="ai-box" style="--border-p: rgba(37,99,235,.25)">
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
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <label class="upload-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            اختر أيقونة (مربّعة يفضّل)
            <input type="file" name="icon" accept="image/*" data-preview="icon-preview" hidden>
          </label>
          <button type="button" id="btn-gen-icon-ai" class="btn-ai" style="padding:9px 16px;font-size:12px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            توليد بالذكاء الاصطناعي
          </button>
        </div>
        <div class="form-hint" id="icon-ai-status">إن لم يتوفر موديل توليد صور، استخدم استيراد Google Play أعلى الصفحة للحصول على الأيقونة الحقيقية.</div>
        <input type="hidden" name="ai_icon_path" id="f-ai-icon-path" value="">
      </div>
      <div class="form-group">
        <label class="form-label">صور التطبيق (Screenshots)</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <label class="upload-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            اختر صور متعددة
            <input type="file" name="screenshots[]" accept="image/*" multiple hidden>
          </label>
          <button type="button" id="btn-gen-shot-ai" class="btn-ai" style="padding:9px 16px;font-size:12px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            توليد صورة بالذكاء الاصطناعي
          </button>
        </div>
        <div class="form-hint" id="shot-ai-status">يمكنك رفع عدة صور مرة واحدة — سيتم ضغطها تلقائياً لـ WebP. يمكنك أيضاً توليد صور بالذكاء الاصطناعي (زر عدة مرات لعدة صور).</div>
        <div id="ai-shots-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px"></div>
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
    <?php if ($isEdit): ?>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <input type="checkbox" name="save_as_new_version" value="1" checked>
      <span style="color:var(--cyan)">إذا تغيّر رقم الإصدار أو رابط التحميل، احفظ الإصدار الحالي (<?= h($app['version'] ?? '—') ?>) في سجل التحديثات قبل الاستبدال — يبقى قابلاً للتصفح والتحميل من صفحة التطبيق</span>
    </label>
    <?php endif; ?>
  </div>

  <button type="submit" class="btn-save" style="margin-bottom:40px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
    <?= $isEdit ? 'حفظ التعديلات' : 'إضافة التطبيق' ?>
  </button>
</form>

<?php if ($isEdit):
  $articlesStmt = $pdo->prepare("SELECT id,title,slug,created_at FROM app_articles WHERE app_id=? ORDER BY created_at DESC");
  $articlesStmt->execute([$app['id']]);
  $appArticles = $articlesStmt->fetchAll();
?>
<div class="panel" style="margin-bottom:40px">
  <h2>مقالات ذات صلة (روابط داخلية للـSEO وAdSense)</h2>
  <p class="form-hint" style="margin-bottom:14px">
    مقالات قصيرة أصلية تربط بصفحة تحميل هذا التطبيق (مثل "كيفية استخدام <?= h($app['name']) ?> للمبتدئين" أو
    "<?= h($app['name']) ?> مقابل بدائله") — تُحسّن الربط الداخلي وتفيد في قبول AdSense.
  </p>
  <button type="button" id="btn-generate-articles" class="btn-ai" data-app-id="<?= (int)$app['id'] ?>">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    توليد 3 مقالات جديدة بالذكاء الاصطناعي
  </button>
  <div class="ai-status" id="generate-articles-status"></div>

  <?php if ($appArticles): ?>
  <table class="admin-table" style="width:100%;margin-top:16px">
    <thead><tr><th>العنوان</th><th>تاريخ الإنشاء</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($appArticles as $art): ?>
      <tr>
        <td data-label="العنوان"><?= h($art['title']) ?></td>
        <td data-label="التاريخ" style="color:var(--muted);font-size:12px"><?= h(time_ago($art['created_at'])) ?></td>
        <td data-label="إجراءات" class="td-actions">
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="<?= h(article_url($art['slug'])) ?>" target="_blank" class="btn-view">عرض</a>
            <a href="admin.php?page=edit-app&id=<?= (int)$app['id'] ?>&del_article=<?= (int)$art['id'] ?>&t=<?= csrf_token() ?>"
               class="btn-del" data-confirm="حذف هذا المقال؟">حذف</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
window.EXISTING_DATA = <?= json_encode([
    'features' => $feat, 'pros' => $pros, 'cons' => $cons,
    'install_steps' => $steps, 'faq' => $faqArr,
], JSON_UNESCAPED_UNICODE) ?>;
</script>

<?php
/* ─────────────── BULK TRENDING APP/GAME GENERATOR ─────────────── */
elseif ($page === 'bulk-generate'): ?>

<div class="admin-header"><h1>توليد تطبيقات رائجة بالجملة</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.8">
    يقترح الذكاء الاصطناعي عدداً اخترته من أكثر التطبيقات/الألعاب بحثاً (معدّلة أو رسمية)، ثم يولّد لكل اسم محتوى كاملاً ويحاول تلقائياً
    العثور على صفحته في Google Play لاستيراد الأيقونة ورابط الصفحة. أي تطبيق يتعذّر جلب رابط تحميل مباشر له يُحفظ <strong style="color:#fbbf24">كمسودة</strong> جاهزة
    — أضف رابط التحميل من صفحة التعديل ثم انشرها.
  </p>
</div>

<div class="panel">
  <div class="form-grid">
    <div class="form-group">
      <label class="form-label">العدد</label>
      <input class="form-input" type="number" id="bg-count" min="1" max="30" value="10">
    </div>
    <div class="form-group">
      <label class="form-label">النوع</label>
      <select class="form-select" id="bg-type">
        <option value="apps">تطبيقات</option>
        <option value="games">ألعاب</option>
        <option value="mixed">تطبيقات وألعاب</option>
      </select>
    </div>
    <div class="form-group full">
      <label class="form-label">تفضيل/مجال إضافي (اختياري)</label>
      <input class="form-input" id="bg-hint" type="text" placeholder="مثال: أدوات VPN، ألعاب أكشن أوفلاين...">
    </div>
  </div>
  <button type="button" id="btn-bg-suggest" class="btn-ai" style="margin-top:10px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    اقترح الأسماء
  </button>
  <div class="ai-status" id="bg-suggest-status"></div>
</div>

<div class="panel" id="bg-names-panel" style="display:none">
  <h2>الأسماء المقترحة — ألغِ تحديد ما لا تريده</h2>
  <div id="bg-names-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px"></div>
  <button type="button" id="btn-bg-create" class="btn-save">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m-7-7h14"/></svg>
    إنشاء الكل
  </button>
  <div style="margin-top:14px;display:none" id="bg-progress">
    <div style="height:8px;background:var(--navy-600);border-radius:6px;overflow:hidden">
      <div id="bg-bar" style="height:100%;width:0%;background:linear-gradient(135deg,var(--cyan),var(--purple));transition:width .3s"></div>
    </div>
    <div id="bg-status" style="font-size:12px;color:var(--muted);margin-top:8px"></div>
  </div>
  <div id="bg-results" style="display:flex;flex-direction:column;gap:8px;margin-top:14px"></div>
</div>

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
        <a href="category.php?slug=<?= h($c['slug']) ?>" target="_blank" class="btn-view">عرض</a>
        <a href="admin.php?page=categories&del_cat=<?= $c['id'] ?>&t=<?= csrf_token() ?>"
           class="btn-del" data-confirm="حذف التصنيف «<?= h($c['name']) ?>»؟">حذف</a>
      </td>
    </tr>
    <tr>
      <td colspan="4" style="padding-top:0">
        <form method="post" action="admin.php?page=categories" class="cat-desc-form" data-cat-name="<?= h($c['name']) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
            <label class="form-label" style="margin:0">وصف SEO للتصنيف (يظهر أعلى صفحة التصنيف)</label>
            <button type="button" class="add-item-btn btn-gen-cat-desc" style="margin:0;padding:6px 14px;font-size:12px">توليد بالذكاء الاصطناعي</button>
          </div>
          <textarea class="form-textarea" name="description" rows="2" placeholder="فقرة قصيرة تشرح هذا التصنيف..."><?= h($c['description'] ?? '') ?></textarea>
          <button type="submit" class="btn-edit" style="margin-top:6px">حفظ الوصف</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
/* ─────────────── CONTACT MESSAGES ─────────────── */
elseif ($page === 'messages'):
  $msgs = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 200")->fetchAll();
?>

<div class="admin-header"><h1>رسائل التواصل</h1></div>

<div class="panel" style="padding:0;overflow:hidden">
<table class="admin-table responsive-cards">
  <thead><tr><th>الاسم</th><th>البريد</th><th>الموضوع</th><th>التاريخ</th><th>الحالة</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($msgs as $m): ?>
  <tr>
    <td data-label="الاسم" style="font-weight:700"><?= h($m['name']) ?></td>
    <td data-label="البريد" style="font-family:var(--f-mono);font-size:12px"><?= h($m['email']) ?></td>
    <td data-label="الموضوع"><?= h($m['subject'] ?: '—') ?></td>
    <td data-label="التاريخ" style="color:var(--muted);font-size:12px"><?= h(time_ago($m['created_at'])) ?></td>
    <td data-label="الحالة"><span class="status-badge <?= $m['status']==='new'?'status-published':'status-draft' ?>"><?= $m['status']==='new'?'جديدة':'مقروءة' ?></span></td>
    <td data-label="إجراءات" class="td-actions">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="admin.php?page=messages&view=<?= $m['id'] ?>#msg-<?= $m['id'] ?>" class="btn-view">عرض</a>
        <a href="admin.php?page=messages&del_msg=<?= $m['id'] ?>&t=<?= csrf_token() ?>" class="btn-del" data-confirm="حذف هذه الرسالة؟">حذف</a>
      </div>
    </td>
  </tr>
  <tr id="msg-<?= $m['id'] ?>">
    <td colspan="6" style="padding-top:0;color:var(--muted);font-size:13px;line-height:1.8;white-space:pre-wrap"><?= h($m['message']) ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$msgs): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:32px">لا توجد رسائل بعد</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php
/* ─────────────── COMMENTS MODERATION ─────────────── */
elseif ($page === 'comments'):
  $cmts = $pdo->query("SELECT c.*, a.name AS app_name, a.slug AS app_slug FROM comments c
      LEFT JOIN apps a ON c.app_id=a.id ORDER BY c.status='pending' DESC, c.created_at DESC LIMIT 200")->fetchAll();
?>

<div class="admin-header"><h1>التعليقات والتقييمات</h1></div>

<div class="panel" style="padding:0;overflow:hidden">
<table class="admin-table responsive-cards">
  <thead><tr><th>التطبيق</th><th>الاسم</th><th>التقييم</th><th>التعليق</th><th>الحالة</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($cmts as $c): ?>
  <tr>
    <td data-label="التطبيق" style="font-weight:700"><?= h($c['app_name'] ?? '—') ?></td>
    <td data-label="الاسم"><?= h($c['name']) ?></td>
    <td data-label="التقييم" style="color:#fbbf24;font-family:var(--f-mono)"><?= str_repeat('★', (int)$c['rating']) ?></td>
    <td data-label="التعليق" style="max-width:280px;color:var(--muted);font-size:13px"><?= h(mb_strimwidth($c['body'], 0, 100, '...')) ?></td>
    <td data-label="الحالة"><span class="status-badge <?= $c['status']==='approved'?'status-published':'status-draft' ?>"><?= $c['status']==='approved'?'منشور':'قيد المراجعة' ?></span></td>
    <td data-label="إجراءات" class="td-actions">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php if ($c['status'] !== 'approved'): ?>
        <a href="admin.php?page=comments&approve=<?= $c['id'] ?>&t=<?= csrf_token() ?>" class="btn-edit">نشر</a>
        <?php endif; ?>
        <?php if ($c['app_slug']): ?>
        <a href="<?= h(app_url($c['app_slug'])) ?>" target="_blank" class="btn-view">عرض التطبيق</a>
        <?php endif; ?>
        <a href="admin.php?page=comments&del_comment=<?= $c['id'] ?>&t=<?= csrf_token() ?>" class="btn-del" data-confirm="حذف هذا التعليق؟">حذف</a>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$cmts): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:32px">لا توجد تعليقات بعد</td></tr><?php endif; ?>
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

  <p style="color:var(--muted);font-size:13px;margin-top:24px;margin-bottom:8px">
    إذا كانت أوصاف بعض التطبيقات القديمة تظهر كفراغ بدل النص، اضغط الزر التالي لإصلاح ترميز النصوص المخزَّنة (يفحص كل التطبيقات ويصلح أي بايتات غير صالحة، آمن للتكرار):
  </p>
  <button type="button" id="btn-repair-encoding" class="btn-save" style="margin-top:4px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v6h6M20 20v-6h-6"/><path d="M4.5 15a8 8 0 0014.5 4.5M19.5 9A8 8 0 005 4.5"/></svg>
    إصلاح ترميز النصوص القديمة
  </button>
  <div id="repair-encoding-result" style="margin-top:12px"></div>
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
    <h2>توليد الصور بالذكاء الاصطناعي <span style="color:var(--muted);font-weight:400">(اختياري)</span></h2>
    <div class="form-group">
      <label class="form-label">معرّف موديل توليد الصور على OpenRouter</label>
      <input class="form-input" type="text" name="openrouter_image_model" value="<?= h(get_cfg($pdo,'openrouter_image_model')) ?>" placeholder="مثال: google/gemini-2.5-flash-image-preview">
      <div class="form-hint">
        نماذج توليد الصور المجانية والموثوقة نادرة جداً على OpenRouter مقارنة بنماذج النصوص — إن تركت هذا الحقل فارغاً أو فشل التوليد،
        استخدم بدلاً منه زر "استيراد من Google Play" الذي يجلب الأيقونة الحقيقية للتطبيق (وهو الخيار الموصى به).
        اطّلع على قائمة الموديلات المتاحة من <a href="https://openrouter.ai/models?modality=text-%3Eimage" target="_blank" style="color:var(--cyan)">openrouter.ai/models</a>.
      </div>
    </div>
  </div>

  <div class="panel">
    <h2>إعدادات الإعلانات</h2>
    <div class="form-hint" style="line-height:1.9">
      يستخدم الموقع الآن Google AdSense فقط (ca-pub-5506877998492189) في كل صفحاته. تمت إزالة شبكة MoneyTag
      (كانت تعمل بنظام النوافذ المنبثقة/popunder) لأن هذا النوع من الإعلانات يخالف صراحةً سياسات ناشري Google
      ويقلل فرص قبول الموقع في AdSense — إعادة إضافتها لاحقاً غير مستحسنة قبل أو بعد الموافقة.
    </div>
  </div>

  <div class="panel">
    <h2>معلومات التواصل</h2>
    <div class="form-group">
      <label class="form-label">البريد الإلكتروني للتواصل / DMCA</label>
      <input class="form-input" type="email" name="contact_email" value="<?= h(get_cfg($pdo,'contact_email')) ?>" placeholder="contact@yourdomain.com">
      <div class="form-hint">يظهر في صفحات اتصل بنا، سياسة الخصوصية، وDMCA.</div>
    </div>
  </div>

  <div class="panel">
    <h2>تقييد وصول لوحة التحكم بعنوان IP <span style="color:var(--muted);font-weight:400">(اختياري)</span></h2>
    <p style="color:var(--muted);font-size:12px;margin-bottom:14px">
      حماية إضافية فوق تسجيل الدخول بكلمة المرور — وليست بديلاً عنه. اترك الحقل فارغاً لتعطيل هذا القيد (الوضع الافتراضي).
      إذا فعّلته، عنوانك الحالي <strong style="color:var(--cyan)"><?= h(client_ip()) ?></strong> يُضاف تلقائياً لمنع إقفال وصولك عن طريق الخطأ.
    </p>
    <div class="form-group">
      <label class="form-label">عناوين IP المسموح بها (سطر لكل عنوان)</label>
      <textarea class="form-textarea" name="admin_ip_allowlist" rows="3" placeholder="اتركه فارغاً لتعطيل القيد"><?= h(get_cfg($pdo,'admin_ip_allowlist')) ?></textarea>
      <div class="form-hint">أي عنوان IP خارج هذه القائمة سيُمنع من فتح admin.php بالكامل، بما فيها صفحة تسجيل الدخول.</div>
    </div>
  </div>

  <button type="submit" class="btn-save">حفظ الإعدادات</button>
</form>

<?php
/* ─────────────── AI ASSISTANT ─────────────── */
elseif ($page === 'assistant'): ?>

<div class="admin-header"><h1>مساعد الذكاء الاصطناعي</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.8">
    اكتب طلبك بلغة طبيعية وسينفّذه المساعد مباشرة عبر إجراءات محدّدة وآمنة فقط: إنشاء مسودة تطبيق بمحتوى مولّد،
    إعادة توليد SEO لتطبيق أو للكل، توليد أيقونة بالذكاء الاصطناعي، أو تعديل إعداد غير حساس.
    المساعد <strong style="color:var(--white)">لا يكتب أو يعدّل ملفات الموقع البرمجية</strong> — كل تغييرات الكود تمر عبر مطوّر الموقع، حفاظاً على أمان السيرفر.
  </p>
  <div style="margin-top:12px;font-size:12px;color:var(--muted)">
    أمثلة: <em>"أنشئ مسودة لتطبيق CapCut Pro"</em> · <em>"حدّث SEO لكل التطبيقات"</em> · <em>"ولّد أيقونة للتطبيق رقم 3"</em>
  </div>
</div>

<div class="panel">
  <div id="assistant-log" style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;max-height:50vh;overflow-y:auto"></div>
  <div class="ai-row">
    <input class="form-input" id="assistant-input" type="text" placeholder="اكتب طلبك هنا...">
    <button type="button" id="btn-assistant-send" class="btn-ai">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
      إرسال
    </button>
  </div>
</div>

<?php endif; ?>

</div><!-- /admin-main -->
</div><!-- /admin-wrap -->

<script src="<?= h(asset_url('assets/js/admin.js')) ?>"></script>
</body>
</html>
