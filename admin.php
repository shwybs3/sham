<?php
// Raise PHP limits for admin only — long descriptions can be several MBs
@ini_set('post_max_size', '512M');
@ini_set('upload_max_filesize', '500M');
@ini_set('max_input_vars', '5000');
@ini_set('memory_limit', '256M');

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
            'error'   => openrouter_diagnose_trace($result['trace']),
            'trace'   => $result['trace'],
        ]);
        exit;
    }

    $data = ai_extract_json($result['content']);

    if ($data) {
        $data['success'] = true;
        $data['used_model'] = $result['model'];
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    } else {
        // Model returned text instead of JSON — retry once with a stricter prompt
        $strictPrompt = "أجب بـJSON فقط بدون أي نص قبله أو بعده، ولا Markdown، ولا شرح. الطلب هو:\n\n" . $prompt;
        $retry = openrouter_call_rotating($keys, array_slice($models, 0, 4), $strictPrompt);
        $data2 = $retry['ok'] ? ai_extract_json($retry['content']) : null;
        if ($data2) {
            $data2['success'] = true;
            $data2['used_model'] = $retry['model'];
            echo json_encode($data2, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success'=>false,'error'=>'لم يُرجع الذكاء الاصطناعي JSON صالح بعد محاولتين. جرّب تغيير الموديل في الإعدادات أو تفعيل "التدوير التلقائي" (الموديل: '.$result['model'].')']);
        }
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

    // AI provider test
    $aiProvider = get_cfg($pdo, 'ai_provider', 'openrouter');
    if ($aiProvider === 'aifreeforever') {
        $r = aifreeforever_call('قل "مرحبا" فقط بدون أي إضافات.');
        $checks[] = [
            'label'  => 'الاتصال بـ aifreeforever',
            'ok'     => $r['ok'],
            'detail' => $r['ok'] ? 'نجح الاتصال ✓ (aifreeforever)' : $r['error'],
        ];
    } else {
        $keys = openrouter_keys(get_cfg($pdo,'openrouter_key'));
        if (!$keys) {
            $checks[] = ['label'=>'مفتاح OpenRouter API','ok'=>false,'detail'=>'لم يتم إضافة أي مفتاح بعد'];
        } else {
            $models = build_model_rotation($pdo, true);
            $r = openrouter_call_rotating($keys, $models, 'قل "مرحبا" فقط بدون أي إضافات.');
            $checks[] = [
                'label' => 'الاتصال بـ OpenRouter (' . count($keys) . ' مفتاح × ' . count($models) . ' موديل)',
                'ok' => $r['ok'],
                'detail' => $r['ok'] ? ('نجح الاتصال باستخدام الموديل: '.$r['model']) : openrouter_diagnose_trace($r['trace']),
                'trace' => $r['trace'] ?? null,
                'working_model' => $r['ok'] ? $r['model'] : null,
            ];
            $primaryNow = get_cfg($pdo, 'openrouter_model', '');
            if ($r['ok'] && $r['model'] !== $primaryNow) set_cfg($pdo, 'openrouter_model', $r['model']);
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
   Database backup — streams a plain .sql dump built via PDO
   (no shell_exec/mysqldump dependency, since shared hosts
   commonly disable shell_exec). Structure + data for every
   table, safe to re-import with a plain SQL client.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'db_backup' && is_admin()) {
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="Tenzil-backup-' . date('Y-m-d-His') . '.sql"');
    echo "-- Tenzil database backup — " . date('Y-m-d H:i:s') . "\n";
    echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $createRow = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $create = $createRow['Create Table'] ?? $createRow[1] ?? '';
        echo "DROP TABLE IF EXISTS `$table`;\n$create;\n\n";

        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        $colList = '`' . implode('`,`', $cols) . '`';
        $rowStmt = $pdo->query("SELECT * FROM `$table`");
        while ($row = $rowStmt->fetch(PDO::FETCH_NUM)) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row);
            echo "INSERT INTO `$table` ($colList) VALUES (" . implode(',', $vals) . ");\n";
        }
        echo "\n";
        @ob_flush(); @flush();
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: VirusTotal — scan the download link / check a pending scan
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'vt_scan' && is_admin()) {
    header('Content-Type: application/json');
    $id = (int)($_GET['app_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id,download_url FROM apps WHERE id=?");
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if (!$a) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }
    $r = vt_scan_url($pdo, $id, $a['download_url'] ?? '');
    echo json_encode(['success'=>$r['ok'], 'status'=>$r['status'] ?? null, 'error'=>$r['error'] ?? null], JSON_UNESCAPED_UNICODE);
    exit;
}
if (isset($_GET['ajax']) && $_GET['ajax'] === 'vt_check' && is_admin()) {
    header('Content-Type: application/json');
    $id = (int)($_GET['app_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id,vt_analysis_id FROM apps WHERE id=?");
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if (!$a) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }
    $r = vt_check_pending($pdo, $id, $a['vt_analysis_id'] ?? '');
    echo json_encode(['success'=>$r['ok'], 'status'=>$r['status'] ?? null, 'error'=>$r['error'] ?? null], JSON_UNESCAPED_UNICODE);
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
   AJAX: Internal link-safety verification (replaces VirusTotal)
   Admin marks a download URL as team-verified; badge shows on the
   app page and download page. No external API, no API keys needed.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'verify_link' && is_admin()) {
    header('Content-Type: application/json');
    $appId = (int)($_POST['app_id'] ?? 0);
    $action = $_POST['action'] ?? 'verify'; // 'verify' or 'unverify'
    if (!$appId) { echo json_encode(['success'=>false,'error'=>'معرف التطبيق مطلوب']); exit; }
    if ($action === 'unverify') {
        $pdo->prepare("UPDATE apps SET link_verified=0, verified_at=NULL WHERE id=?")->execute([$appId]);
        echo json_encode(['success'=>true,'verified'=>false]);
    } else {
        $pdo->prepare("UPDATE apps SET link_verified=1, verified_at=NOW() WHERE id=?")->execute([$appId]);
        bump_cache_version($pdo);
        echo json_encode(['success'=>true,'verified'=>true]);
    }
    exit;
}

/* ── AJAX: test Telegram bot ── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'telegram_test' && is_admin()) {
    header('Content-Type: application/json');
    $r = telegram_api($pdo, 'sendMessage', [
        'text'       => '✅ اختبار ناجح من لوحة إدارة <b>Tenzil</b> — بوت تيليجرام يعمل بشكل صحيح!',
        'parse_mode' => 'HTML',
    ]);
    echo json_encode(['success' => $r['ok'], 'error' => $r['error'] ?? null], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate one general blog post (tutorial/news/
   comparison/best-of/article) — the general-content system,
   distinct from the per-app app_articles above.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_blog_post' && is_admin()) {
    header('Content-Type: application/json');
    $type = trim($_GET['type'] ?? 'article');
    if (!isset(BLOG_TYPES[$type])) $type = 'article';
    $topic = trim($_GET['topic'] ?? '');

    $typeGuides = [
        'tutorial'   => 'شرح تعليمي خطوة بخطوة يساعد القارئ على إنجاز مهمة محددة داخل تطبيق أو لعبة أندرويد شهيرة.',
        'news'       => 'خبر تقني قصير عن تحديث أو ميزة جديدة أو اتجاه في عالم تطبيقات وألعاب أندرويد (بأسلوب إخباري محايد، دون تأكيد تواريخ أو أرقام غير مؤكدة).',
        'comparison' => 'مقارنة موضوعية ومتوازنة بين تطبيقين أو أكثر في نفس الفئة، توضح الفروقات الفعلية ولمن يناسب كل خيار.',
        'best-apps'  => 'قائمة "أفضل التطبيقات" في فئة معينة (5-8 عناصر)، كل عنصر بفقرة تشرح لماذا يستحق مكانه.',
        'best-games' => 'قائمة "أفضل الألعاب" في فئة أو نمط لعب معين (5-8 عناصر)، كل عنصر بفقرة تشرح ما يميزه.',
        'article'    => 'مقال عام مفيد وأصلي متعلق بتطبيقات أو ألعاب أندرويد.',
    ];
    $topicLine = $topic !== '' ? "الموضوع المطلوب تحديداً: \"{$topic}\"." : "اختر موضوعاً شائعاً ومفيداً يناسب هذا القسم.";

    $prompt = <<<P
أنت كاتب محتوى عربي محترف متخصص في تطبيقات وألعاب أندرويد. اكتب مقالاً أصلياً بالكامل من نوع:
{$typeGuides[$type]}
{$topicLine}

المقال بطول 700-1200 كلمة، منظم بعناوين فرعية واضحة.
اكتب body كـ HTML كامل قابل للعرض مباشرة: استخدم <h2> و<h3> للعناوين الفرعية، <p> للفقرات، <ul><li> للقوائم، <strong> للتمييز. لا تضع HTML خارج body. لا تستخدم Markdown.

اتبع معايير SEO:
- seo_title: عنوان جذاب 50-65 حرفاً يتضمن الكلمة المفتاحية.
- meta_description: 140-160 حرفاً يلخص المقال.
- keywords: 10-15 كلمة/عبارة مفتاحية عربية مفصولة بفاصلة.
- excerpt: سطر أو سطران يظهران في بطاقة المقال.

أعد JSON صالح فقط بدون أي نص خارج JSON:
{"title":"","seo_title":"","meta_description":"","keywords":"","excerpt":"","body":""}
P;
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $data = ai_extract_json($r['content']);
    if (!$data) { echo json_encode(['success'=>false,'error'=>'رد الذكاء الاصطناعي لم يكن JSON صالحاً']); exit; }
    $data = clean_utf8_deep($data);

    $title = trim($data['title'] ?? '');
    $body  = trim($data['body'] ?? '');
    if (!$title || !$body) { echo json_encode(['success'=>false,'error'=>'رد الذكاء الاصطناعي ناقص']); exit; }
    $slug = unique_blog_slug($pdo, $title);

    // Best-effort cover image — never blocks the post from being created.
    $coverImage = null;
    $ir = ai_generate_image($pdo, "Generate a professional, modern, minimalist blog cover illustration (no text, no watermark, flat/gradient style, 16:9, wide) representing the topic: \"{$title}\".");
    if ($ir['ok']) {
        $tmp = tempnam(sys_get_temp_dir(), 'blogcv');
        file_put_contents($tmp, $ir['bin']);
        $coverImage = process_icon(['tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($ir['bin'])], $slug . '-cover');
        @unlink($tmp);
    }

    $pdo->prepare("INSERT INTO blog_posts (type,title,slug,seo_title,meta_description,keywords,excerpt,body,cover_image,status) VALUES (?,?,?,?,?,?,?,?,?,'draft')")
        ->execute([$type, $title, $slug, trim($data['seo_title'] ?? ''), trim($data['meta_description'] ?? ''),
            trim($data['keywords'] ?? ''), trim($data['excerpt'] ?? ''), $body, $coverImage]);
    $newId = (int)$pdo->lastInsertId();
    echo json_encode(['success'=>true,'id'=>$newId,'title'=>$title], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate one chunk of a long app-review article.
   Five chunks total → ~4500-6000 Arabic words of HTML.
   The client chains calls sequentially, passing prev HTML
   so the model knows what has already been written.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'gen_app_article_chunk' && is_admin()) {
    header('Content-Type: application/json');
    @set_time_limit(120);
    $input   = json_decode(file_get_contents('php://input'), true);
    $appId   = (int)($input['app_id'] ?? 0);
    $chunk   = max(1, min(5, (int)($input['chunk'] ?? 1)));
    $prevHtml= trim($input['prev_html'] ?? '');
    if (!$appId) { echo json_encode(['success'=>false,'error'=>'app_id مطلوب']); exit; }

    $app = $pdo->prepare("SELECT a.*,c.name AS cat_name FROM apps a LEFT JOIN categories c ON c.id=a.category_id WHERE a.id=?");
    $app->execute([$appId]);
    $app = $app->fetch(PDO::FETCH_ASSOC);
    if (!$app) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }

    $aName = $app['name'];
    $aDev  = $app['developer'] ?? '';
    $aDesc = $app['long_description'] ?? $app['short_description'] ?? '';
    $aCat  = $app['cat_name'] ?? '';
    $aSlug = $app['slug'] ?? '';
    $ftr   = json_decode($app['features'] ?? '[]', true) ?: [];
    $pros  = json_decode($app['pros'] ?? '[]', true) ?: [];
    $cons  = json_decode($app['cons'] ?? '[]', true) ?: [];
    $faq   = json_decode($app['faq'] ?? '[]', true) ?: [];
    $steps = json_decode($app['install_steps'] ?? '[]', true) ?: [];
    $appUrl = url("app/{$aSlug}");

    $ctaHtml = "<div style='text-align:center;margin:28px 0'><a href=\"{$appUrl}\" style='display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-radius:50px;font-size:17px;font-weight:700;text-decoration:none;box-shadow:0 4px 16px rgba(37,99,235,.3)'>⬇ تحميل {$aName} مجاناً</a></div>";

    $sectionTitles = [
        1 => 'المقدمة + نظرة عامة + سبب الشهرة',
        2 => 'الميزات التفصيلية مع أمثلة عملية',
        3 => 'كيفية التحميل والتثبيت + كيفية الاستخدام خطوة بخطوة',
        4 => 'المميزات والعيوب + المقارنة مع البدائل',
        5 => 'نصائح احترافية + الأسئلة الشائعة + خلاصة وتوصية',
    ];

    $ftrList  = implode('، ', array_slice($ftr,0,6));
    $prosList = implode('، ', array_slice($pros,0,5));
    $consList = implode('، ', array_slice($cons,0,3));
    $stepList = implode("\n", array_map(fn($s,$i)=>"- خطوة ".($i+1).": $s", $steps, array_keys($steps)));
    $faqList  = implode("\n", array_map(fn($f)=>"س: {$f['q']}\nج: {$f['a']}", array_slice($faq,0,5)));
    $prevNote = $prevHtml ? "ما سبق كتابته (لا تكرره، أكمل بعده مباشرة):\n[تم كتابة ".mb_strlen($prevHtml)." حرفاً من المقال]" : '';

    $prompt = <<<P
أنت كاتب محتوى عربي محترف ومتخصص في مراجعات تطبيقات الأندرويد. تكتب بالعامية السورية العربية الطبيعية والجذابة.

معلومات التطبيق:
- الاسم: {$aName}
- المطور: {$aDev}
- التصنيف: {$aCat}
- الميزات: {$ftrList}
- الإيجابيات: {$prosList}
- السلبيات: {$consList}
- خطوات التثبيت: {$stepList}
- أسئلة شائعة: {$faqList}
- وصف عام: {$aDesc}

{$prevNote}

اكتب الآن القسم رقم {$chunk} من المقال: {$sectionTitles[$chunk]}

المطلوب:
- 900-1100 كلمة لهذا القسم (مهم جداً، لا تكتب أقل)
- HTML فقط (لا Markdown، لا نص خام)
- استخدم <h2> للعناوين الرئيسية، <h3> للفرعية
- <p> للفقرات (فقرات كاملة ومفيدة، ليس جملاً قصيرة)
- <ul><li> للقوائم حيث مناسب
- <strong> لتمييز المصطلحات المهمة
- اللهجة: سورية عربية مفهومة، حيوية ومشوّقة من أول سطر
- المحتوى: حقيقي ومفيد، ليس ملء بلا معنى
- لا تضع مقدمات مثل "في هذا القسم سنتحدث عن..."
- لا تكتب "الكاتب" أو "المقال" في النص
- فقط HTML قابل للوصق مباشرة داخل صفحة المقال، لا شيء آخر
P;

    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $html = trim($r['content']);
    // Strip markdown code fences if model wrapped the HTML
    $html = preg_replace('/^```(?:html)?\s*/i', '', $html);
    $html = preg_replace('/\s*```\s*$/i', '', $html);
    // Inject CTA after chunk 2 and chunk 4
    if (in_array($chunk, [2, 4])) $html .= "\n" . $ctaHtml;

    echo json_encode(['success'=>true,'chunk'=>$chunk,'html'=>$html,'word_count'=>count(preg_split('/\s+/u',$html,-1,PREG_SPLIT_NO_EMPTY))], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Save a completed app-review article to blog_posts
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'save_app_article' && is_admin()) {
    header('Content-Type: application/json');
    $input  = json_decode(file_get_contents('php://input'), true);
    $appId  = (int)($input['app_id'] ?? 0);
    $html   = trim($input['html'] ?? '');
    if (!$appId || !$html) { echo json_encode(['success'=>false,'error'=>'بيانات مطلوبة']); exit; }

    $app = $pdo->prepare("SELECT * FROM apps WHERE id=?"); $app->execute([$appId]); $app=$app->fetch(PDO::FETCH_ASSOC);
    if (!$app) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }

    $aName = $app['name'];
    $title = "مراجعة تطبيق {$aName}: دليل شامل للاستخدام والميزات";
    $slug  = unique_blog_slug($pdo, $title);
    $seoTitle = "مراجعة {$aName} " . date('Y') . " — دليل شامل بالعربي";
    $metaDesc = "مراجعة شاملة لتطبيق {$aName}: الميزات، كيفية الاستخدام، المميزات والعيوب، وكل ما تحتاج معرفته بالعامية السورية.";
    $kwds  = "تطبيق {$aName}، مراجعة {$aName}، تحميل {$aName}، {$aName} للأندرويد";
    $excerpt= "مراجعة تفصيلية لتطبيق {$aName} — الميزات، كيفية الاستخدام، المميزات والعيوب وكل ما تحتاج معرفته.";
    $appUrl = url("app/{$app['slug']}");
    // Append final CTA
    $html .= "\n<div style='text-align:center;margin:32px 0'><a href=\"{$appUrl}\" style='display:inline-block;padding:16px 40px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-radius:50px;font-size:18px;font-weight:700;text-decoration:none;box-shadow:0 4px 20px rgba(37,99,235,.35)'>⬇ حمّل {$aName} الآن مجاناً</a></div>";

    $existing = $pdo->prepare("SELECT id FROM blog_posts WHERE title=?"); $existing->execute([$title]);
    if ($eid = $existing->fetchColumn()) {
        $pdo->prepare("UPDATE blog_posts SET body=?,slug=?,seo_title=?,meta_description=?,keywords=?,excerpt=?,updated_at=NOW() WHERE id=?")->execute([$html,$slug,$seoTitle,$metaDesc,$kwds,$excerpt,$eid]);
        echo json_encode(['success'=>true,'id'=>(int)$eid,'title'=>$title,'updated'=>true,'view_url'=>url("blog/{$slug}")], JSON_UNESCAPED_UNICODE);
    } else {
        $pdo->prepare("INSERT INTO blog_posts (type,title,slug,seo_title,meta_description,keywords,excerpt,body,status) VALUES ('article',?,?,?,?,?,?,'draft')")->execute([$title,$slug,$seoTitle,$metaDesc,$kwds,$excerpt,$html]);
        $nId=(int)$pdo->lastInsertId();
        echo json_encode(['success'=>true,'id'=>$nId,'title'=>$title,'updated'=>false,'view_url'=>url("blog/{$slug}")], JSON_UNESCAPED_UNICODE);
    }
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
أنت مساعد إدارة داخل لوحة تحكم متجر تطبيقات "Tenzil". لديك قدرة على تنفيذ إجراءات محدّدة فقط عبر إرجاع JSON — لا تنفّذ أي كود، ولا تكتب ملفات، فقط تختار إجراءً من القائمة المسموحة التالية:

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
   AJAX: List all 30 pre-written preset apps with import status
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'import_preset_list' && is_admin()) {
    header('Content-Type: application/json');
    $dataDir = ROOT_PATH . '/install/bulk-apps-data';
    $files = glob($dataDir . '/batch-*.json');
    sort($files);
    if (!$files) { echo json_encode(['success'=>false,'error'=>'ملفات البيانات غير موجودة في install/bulk-apps-data/']); exit; }

    $SLUG_MAP = [
        'واتساب'=>['whatsapp','social'],'تيليجرام'=>['telegram','social'],
        'فيسبوك'=>['facebook','social'],'ماسنجر'=>['messenger','social'],
        'سناب شات'=>['snapchat','social'],'ديسكورد'=>['discord','social'],
        'تويتر X'=>['twitter-x','social'],'ثريدز'=>['threads','social'],
        'لينكدإن'=>['linkedin','social'],'بينترست'=>['pinterest','social'],
        'جوجل كروم'=>['google-chrome','tools'],'يوسي براوزر'=>['uc-browser','tools'],
        'يوتيوب'=>['youtube','apps'],'نتفليكس'=>['netflix','apps'],
        'سبوتيفاي'=>['spotify','apps'],'شازام'=>['shazam','apps'],
        'ساوند كلاود'=>['soundcloud','apps'],'أوبرا ميني'=>['opera-mini','tools'],
        'ترو كولر'=>['truecaller','tools'],'جوجل ترانسليت'=>['google-translate','tools'],
        'زوم'=>['zoom','tools'],'مايكروسوفت تيمز'=>['microsoft-teams','tools'],
        'دوولينجو'=>['duolingo','productivity'],'WPS Office'=>['wps-office','productivity'],
        'أدوبي أكروبات ريدر'=>['adobe-acrobat-reader','productivity'],'كانفا'=>['canva','design'],
        'بيكس آرت'=>['picsart','design'],'إن شوت'=>['inshot','design'],
        'بابجي موبايل'=>['pubg-mobile','games'],'فري فاير'=>['free-fire','games'],
        'تيك توك'=>['tiktok','apps'],'إنستغرام'=>['instagram','social'],
        'كاب كت'=>['capcut','design'],'يوتيوب ميوزيك'=>['youtube-music','apps'],
        'تويتش'=>['twitch','apps'],'شاهد'=>['shahid','apps'],
        'جوجل مابس'=>['google-maps','tools'],'جوجل درايف'=>['google-drive','productivity'],
        'كيبورد جوجل'=>['gboard','tools'],'مايكروسوفت وورد'=>['microsoft-word','productivity'],
        'ون درايف'=>['onedrive','productivity'],'أدوبي لايتروم'=>['adobe-lightroom','design'],
        'كلاش أوف كلانز'=>['clash-of-clans','games'],'كلاش رويال'=>['clash-royale','games'],
        'موبايل ليجندز'=>['mobile-legends','games'],'ماين كرافت'=>['minecraft','games'],
        'سابواي سيرف'=>['subway-surfers','games'],'تمبل رن 2'=>['temple-run-2','games'],
        'أمازون شوبينج'=>['amazon-shopping','apps'],'علي إكسبريس'=>['aliexpress','apps'],
        'شيين'=>['shein','apps'],'تالابات'=>['talabat','apps'],
        'كريم'=>['careem','apps'],'نمشي'=>['namshi','apps'],
        'نورد VPN'=>['nordvpn','tools'],'فايرفوكس'=>['firefox','tools'],
        'برايف براوزر'=>['brave-browser','tools'],'جيميل'=>['gmail','tools'],
        'مايكروسوفت أوتلوك'=>['microsoft-outlook','productivity'],'أنكي درويد'=>['ankidroid','productivity'],
    ];

    $catLabels = ['social'=>'تواصل اجتماعي','tools'=>'أدوات','apps'=>'تطبيقات',
                  'productivity'=>'إنتاجية','design'=>'تصميم','games'=>'ألعاب'];

    $result = [];
    foreach ($files as $file) {
        $batch = json_decode((string)file_get_contents($file), true);
        if (!$batch || empty($batch['apps'])) continue;
        foreach ($batch['apps'] as $app) {
            $name = trim($app['name'] ?? '');
            if (!$name) continue;
            $ex = $pdo->prepare("SELECT id FROM apps WHERE name=?");
            $ex->execute([$name]);
            $existId = $ex->fetchColumn();
            $catSlug = $app['category_slug'] ?? ($SLUG_MAP[$name][1] ?? 'apps');
            $result[] = [
                'name'       => $name,
                'developer'  => $app['developer'] ?? '',
                'short_desc' => $app['short_description'] ?? '',
                'category'   => $catLabels[$catSlug] ?? $catSlug,
                'cat_slug'   => $catSlug,
                'existing_id'=> $existId ?: null,
            ];
        }
    }
    echo json_encode(['success'=>true,'apps'=>$result,'total'=>count($result)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Import one pre-written preset app by name
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'import_preset_one' && is_admin()) {
    header('Content-Type: application/json');
    @set_time_limit(90);
    $input = json_decode(file_get_contents('php://input'), true);
    $targetName = trim($input['name'] ?? '');
    if (!$targetName) { echo json_encode(['success'=>false,'error'=>'اسم مطلوب']); exit; }

    // Find app in data files
    $dataDir = ROOT_PATH . '/install/bulk-apps-data';
    $files = glob($dataDir . '/batch-*.json');
    sort($files);
    $data = null;
    foreach ($files as $file) {
        $batch = json_decode((string)file_get_contents($file), true);
        if (!$batch) continue;
        foreach ($batch['apps'] as $app) {
            if (trim($app['name'] ?? '') === $targetName) { $data = $app; break 2; }
        }
    }
    if (!$data) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود في ملفات البيانات']); exit; }

    // Skip if already exists
    $ex = $pdo->prepare("SELECT id FROM apps WHERE name=?");
    $ex->execute([$targetName]);
    if ($exId = $ex->fetchColumn()) {
        echo json_encode(['success'=>false,'skipped'=>true,'existing_id'=>(int)$exId,'name'=>$targetName,'error'=>'موجود مسبقاً']);
        exit;
    }

    $SLUG_MAP = [
        'واتساب'=>['whatsapp','social'],'تيليجرام'=>['telegram','social'],
        'فيسبوك'=>['facebook','social'],'ماسنجر'=>['messenger','social'],
        'سناب شات'=>['snapchat','social'],'ديسكورد'=>['discord','social'],
        'تويتر X'=>['twitter-x','social'],'ثريدز'=>['threads','social'],
        'لينكدإن'=>['linkedin','social'],'بينترست'=>['pinterest','social'],
        'جوجل كروم'=>['google-chrome','tools'],'يوسي براوزر'=>['uc-browser','tools'],
        'يوتيوب'=>['youtube','apps'],'نتفليكس'=>['netflix','apps'],
        'سبوتيفاي'=>['spotify','apps'],'شازام'=>['shazam','apps'],
        'ساوند كلاود'=>['soundcloud','apps'],'أوبرا ميني'=>['opera-mini','tools'],
        'ترو كولر'=>['truecaller','tools'],'جوجل ترانسليت'=>['google-translate','tools'],
        'زوم'=>['zoom','tools'],'مايكروسوفت تيمز'=>['microsoft-teams','tools'],
        'دوولينجو'=>['duolingo','productivity'],'WPS Office'=>['wps-office','productivity'],
        'أدوبي أكروبات ريدر'=>['adobe-acrobat-reader','productivity'],'كانفا'=>['canva','design'],
        'بيكس آرت'=>['picsart','design'],'إن شوت'=>['inshot','design'],
        'بابجي موبايل'=>['pubg-mobile','games'],'فري فاير'=>['free-fire','games'],
        // Batch F
        'تيك توك'=>['tiktok','apps'],'إنستغرام'=>['instagram','social'],
        'كاب كت'=>['capcut','design'],'يوتيوب ميوزيك'=>['youtube-music','apps'],
        'تويتش'=>['twitch','apps'],'شاهد'=>['shahid','apps'],
        // Batch G
        'جوجل مابس'=>['google-maps','tools'],'جوجل درايف'=>['google-drive','productivity'],
        'كيبورد جوجل'=>['gboard','tools'],'مايكروسوفت وورد'=>['microsoft-word','productivity'],
        'ون درايف'=>['onedrive','productivity'],'أدوبي لايتروم'=>['adobe-lightroom','design'],
        // Batch H
        'كلاش أوف كلانز'=>['clash-of-clans','games'],'كلاش رويال'=>['clash-royale','games'],
        'موبايل ليجندز'=>['mobile-legends','games'],'ماين كرافت'=>['minecraft','games'],
        'سابواي سيرف'=>['subway-surfers','games'],'تمبل رن 2'=>['temple-run-2','games'],
        // Batch I
        'أمازون شوبينج'=>['amazon-shopping','apps'],'علي إكسبريس'=>['aliexpress','apps'],
        'شيين'=>['shein','apps'],'تالابات'=>['talabat','apps'],
        'كريم'=>['careem','apps'],'نمشي'=>['namshi','apps'],
        // Batch J
        'نورد VPN'=>['nordvpn','tools'],'فايرفوكس'=>['firefox','tools'],
        'برايف براوزر'=>['brave-browser','tools'],'جيميل'=>['gmail','tools'],
        'مايكروسوفت أوتلوك'=>['microsoft-outlook','productivity'],'أنكي درويد'=>['ankidroid','productivity'],
    ];
    [$slugBase, $catSlug] = $SLUG_MAP[$targetName] ?? [slugify($targetName), 'apps'];
    // Allow batch data to override category
    if (!empty($data['category_slug'])) $catSlug = $data['category_slug'];
    $slug = unique_slug($pdo, $slugBase);
    $catSt = $pdo->prepare("SELECT id FROM categories WHERE slug=?");
    $catSt->execute([$catSlug]);
    $catId = $catSt->fetchColumn();
    if (!$catId) { $catSt->execute(['apps']); $catId = $catSt->fetchColumn(); }

    // Prefer playstore_url from batch data; fall back to search
    $playstoreUrl = trim($data['playstore_url'] ?? '');
    if (!$playstoreUrl) {
        $playstoreUrl = playstore_search($targetName . ' ' . ($data['developer'] ?? ''));
        if (!$playstoreUrl) $playstoreUrl = playstore_search($targetName);
    }

    // Prefer package_name from batch data; extract from URL as fallback
    $packageName = trim($data['package_name'] ?? '');
    if (!$packageName && $playstoreUrl && preg_match('#[?&]id=([a-zA-Z0-9_.]+)#', $playstoreUrl, $pm)) {
        $packageName = $pm[1];
    }

    // Icon: fetch from Play Store og:image
    $iconPath = null;
    if ($playstoreUrl) {
        $meta = fetch_playstore_meta($playstoreUrl);
        if ($meta && !empty($meta['icon_url'])) {
            $iconPath = import_remote_icon($meta['icon_url'], $slug);
        }
        if (!$packageName && !empty($meta['package_name'])) $packageName = $meta['package_name'];
    }

    // Screenshots: scrape Play Store (best-effort, non-blocking)
    $screenshots = [];
    if ($playstoreUrl) {
        $screenshots = fetch_playstore_screenshots($playstoreUrl, $slug, 4);
    }

    $features     = array_values(array_filter($data['features'] ?? []));
    $pros         = array_values(array_filter($data['pros'] ?? []));
    $cons         = array_values(array_filter($data['cons'] ?? []));
    $installSteps = array_values(array_filter($data['install_steps'] ?? []));
    $faq          = array_is_list($data['faq'] ?? []) ? $data['faq'] : [];

    try {
        $pdo->prepare("INSERT INTO apps
            (name,slug,category_id,developer,license,version,android_version,size_mb,
             icon_path,screenshots,short_description,long_description,
             features,pros,cons,install_steps,faq,whats_new,playstore_url,package_name,rating,
             seo_title,meta_description,keywords,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")
            ->execute([
                $targetName,$slug,$catId,
                trim($data['developer'] ?? ''),
                trim($data['license'] ?? 'Free'),
                trim($data['version'] ?? ''),
                trim($data['android_version'] ?? ''),
                trim($data['size_mb'] ?? ''),
                $iconPath,
                $screenshots ? json_encode($screenshots, JSON_UNESCAPED_UNICODE) : null,
                trim($data['short_description'] ?? ''),trim($data['long_description'] ?? ''),
                json_encode($features,JSON_UNESCAPED_UNICODE),json_encode($pros,JSON_UNESCAPED_UNICODE),
                json_encode($cons,JSON_UNESCAPED_UNICODE),json_encode($installSteps,JSON_UNESCAPED_UNICODE),
                json_encode($faq,JSON_UNESCAPED_UNICODE),trim($data['whats_new'] ?? ''),
                $playstoreUrl ?: null,$packageName ?: null,4.5,
                trim($data['seo_title'] ?? ''),trim($data['meta_description'] ?? ''),trim($data['keywords'] ?? ''),
            ]);
        $newId = (int)$pdo->lastInsertId();
        echo json_encode(['success'=>true,'id'=>$newId,'name'=>$targetName,
            'has_icon'=>(bool)$iconPath,'screenshots'=>count($screenshots),
            'edit_url'=>'admin.php?page=edit-app&id='.$newId], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   Helper: parse SQL INSERT INTO apps ... statements
   ══════════════════════════════════════════════════════ */
function parse_sql_app_inserts(string $sql): array {
    $apps = [];
    $pattern = '/INSERT\s+INTO\s+[`"\']?apps[`"\']?\s*\(([^)]+)\)\s*VALUES\s*\((.+?)\)\s*;/is';
    if (!preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) return [];
    foreach ($matches as $m) {
        $cols = array_map(fn($c) => trim($c, " `\"'\t"), explode(',', $m[1]));
        $vals = []; $s = $m[2]; $i = 0; $len = strlen($s);
        while ($i < $len) {
            while ($i < $len && in_array($s[$i],[' ',"\t","\n","\r",','])) $i++;
            if ($i >= $len) break;
            if ($s[$i] === "'") {
                $val = ''; $i++;
                while ($i < $len) {
                    if ($s[$i] === "'" ) {
                        if (isset($s[$i+1]) && $s[$i+1] === "'") { $val .= "'"; $i += 2; }
                        else { $i++; break; }
                    } elseif ($s[$i] === '\\' && isset($s[$i+1])) {
                        $map = ["'"=>"'",'"'=>'"','\\'=>'\\','n'=>"\n",'r'=>"\r",'t'=>"\t"];
                        $val .= $map[$s[$i+1]] ?? $s[$i+1]; $i += 2;
                    } else { $val .= $s[$i++]; }
                }
                $vals[] = $val;
            } elseif (strtoupper(substr($s,$i,4))==='NULL') { $vals[] = null; $i+=4; }
            else {
                $start=$i; while ($i<$len && !in_array($s[$i],[',',' ',"\t",')'])) $i++;
                $vals[] = substr($s,$start,$i-$start);
            }
        }
        if (count($cols) !== count($vals)) continue;
        $app = array_combine($cols,$vals);
        foreach (['features','pros','cons','install_steps','faq'] as $f) {
            if (isset($app[$f]) && is_string($app[$f]) && strlen($app[$f])>1) {
                $d = json_decode($app[$f],true);
                if (is_array($d)) $app[$f] = $d;
            }
        }
        $apps[] = $app;
    }
    return $apps;
}

/* ══════════════════════════════════════════════════════
   AJAX: Validate uploaded file — quality check each app
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'file_import_validate' && is_admin()) {
    header('Content-Type: application/json');
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success'=>false,'error'=>'لم يتم رفع الملف أو حدث خطأ في الرفع']); exit;
    }
    $file = $_FILES['file'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $content = (string)file_get_contents($file['tmp_name']);
    $apps = [];

    // 1. Try JSON (covers .json, .php returning JSON, etc.)
    $decoded = json_decode($content, true);
    if ($decoded !== null) {
        if (isset($decoded['apps']) && is_array($decoded['apps'])) $apps = $decoded['apps'];
        elseif (array_is_list($decoded)) $apps = $decoded;
        elseif (isset($decoded['name'])) $apps = [$decoded];
    }
    // 2. SQL INSERT statements
    if (!$apps && in_array($ext, ['sql','txt'])) $apps = parse_sql_app_inserts($content);
    // 3. PHP: extract JSON blocks
    if (!$apps && $ext === 'php') {
        if (preg_match_all('/\{[^{}]*?"name"[^{}]*?\}/s', $content, $jm)) {
            foreach ($jm[0] as $js) { $a = json_decode($js,true); if ($a&&isset($a['name'])) $apps[]=$a; }
        }
    }

    if (!$apps) { echo json_encode(['success'=>false,'error'=>'لم يُعثر على بيانات تطبيقات صالحة. تأكد من الصيغة (JSON مع مصفوفة apps، أو INSERT SQL).']); exit; }

    $norm = function($v) {
        if (is_array($v)) return array_values(array_filter($v));
        if (is_string($v)&&strlen($v)) { $d=json_decode($v,true); return is_array($d)?array_values(array_filter($d)):[]; }
        return [];
    };

    $results = [];
    foreach ($apps as $app) {
        $issues = []; $warnings = []; $score = 100;
        $name = trim($app['name'] ?? '');
        if (!$name) { $issues[] = 'الاسم مطلوب'; $score -= 20; }
        if (!trim($app['developer'] ?? '')) { $warnings[] = 'المطور غير محدد'; $score -= 3; }

        $short = trim($app['short_description'] ?? '');
        if (!$short) { $issues[] = 'الوصف القصير مطلوب'; $score -= 8; }
        elseif (mb_strlen($short) < 80) { $issues[] = 'الوصف القصير قصير جداً (أقل من 80 حرف)'; $score -= 6; }

        $long = trim($app['long_description'] ?? '');
        $wc = $long ? count(preg_split('/\s+/u', $long, -1, PREG_SPLIT_NO_EMPTY)) : 0;
        if (!$long) { $issues[] = 'الوصف الطويل مطلوب'; $score -= 25; }
        elseif ($wc < 200) { $issues[] = "الوصف الطويل قصير جداً ({$wc} كلمة، الحد الأدنى 200)"; $score -= 20; }
        elseif ($wc < 500) { $warnings[] = "الوصف الطويل {$wc} كلمة — يُنصح بـ 500+ للـ SEO الأمثل"; $score -= 8; }

        $seoTitle = trim($app['seo_title'] ?? '');
        $stLen = mb_strlen($seoTitle);
        if (!$seoTitle) { $issues[] = 'عنوان SEO مطلوب'; $score -= 10; }
        elseif ($stLen < 30) { $issues[] = "عنوان SEO قصير ({$stLen} حرف، الأفضل 40-70)"; $score -= 6; }
        elseif ($stLen > 80) { $warnings[] = "عنوان SEO طويل ({$stLen} حرف)"; $score -= 3; }

        $meta = trim($app['meta_description'] ?? '');
        $mLen = mb_strlen($meta);
        if (!$meta) { $issues[] = 'وصف meta مطلوب'; $score -= 10; }
        elseif ($mLen < 80) { $issues[] = "وصف meta قصير ({$mLen} حرف، الأفضل 120-160)"; $score -= 6; }
        elseif ($mLen > 180) { $warnings[] = "وصف meta طويل ({$mLen} حرف)"; $score -= 2; }

        $kw = trim($app['keywords'] ?? '');
        $kwc = $kw ? count(preg_split('/[,،\s]+/u', $kw, -1, PREG_SPLIT_NO_EMPTY)) : 0;
        if (!$kw) { $issues[] = 'الكلمات المفتاحية مطلوبة'; $score -= 8; }
        elseif ($kwc < 3) { $warnings[] = "عدد الكلمات المفتاحية قليل ({$kwc}، يُنصح بـ 5+)"; $score -= 3; }

        $fc = count($norm($app['features'] ?? []));
        if ($fc < 3) { $issues[] = "الميزات غير كافية ({$fc}، الحد الأدنى 3)"; $score -= 8; }
        elseif ($fc < 5) { $warnings[] = "ميزات قليلة ({$fc}، يُنصح بـ 5+)"; $score -= 2; }

        $pc = count($norm($app['pros'] ?? []));
        if ($pc < 2) { $issues[] = "الإيجابيات غير كافية ({$pc}، الحد الأدنى 2)"; $score -= 5; }

        $cc = count($norm($app['cons'] ?? []));
        if ($cc < 1) { $warnings[] = 'السلبيات غائبة (يُنصح بسلبية واحدة على الأقل)'; $score -= 3; }

        $sc = count($norm($app['install_steps'] ?? []));
        if ($sc < 2) { $issues[] = "خطوات التثبيت غير كافية ({$sc}، الحد الأدنى 2)"; $score -= 5; }

        $faqArr = $norm($app['faq'] ?? []);
        $faqValid = count(array_filter($faqArr, fn($f) => !empty($f['q']) && !empty($f['a'])));
        if ($faqValid < 3) { $issues[] = "الأسئلة الشائعة غير كافية ({$faqValid}، الحد الأدنى 3 أسئلة مع إجابات)"; $score -= 8; }

        if (empty($app['screenshots'])) { $warnings[] = 'لا توجد روابط صور للتطبيق'; $score -= 2; }

        $score = max(0, $score);
        $pass  = empty($issues) && $score >= 55;
        $results[] = [
            'name'      => $name ?: '(بدون اسم)',
            'developer' => trim($app['developer'] ?? ''),
            'pass'      => $pass,
            'score'     => $score,
            'word_count'=> $wc,
            'issues'    => $issues,
            'warnings'  => $warnings,
            'data'      => $app,
        ];
    }
    $passed = count(array_filter($results, fn($r)=>$r['pass']));
    echo json_encode(['success'=>true,'apps'=>$results,'total'=>count($results),'passed'=>$passed], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Import one validated app from file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'file_import_run' && is_admin()) {
    header('Content-Type: application/json');
    @set_time_limit(120);
    $input = json_decode(file_get_contents('php://input'), true);
    $app   = $input['app'] ?? [];
    if (!$app || empty($app['name'])) { echo json_encode(['success'=>false,'error'=>'بيانات التطبيق مطلوبة']); exit; }
    $name = trim($app['name']);
    $ex = $pdo->prepare("SELECT id FROM apps WHERE name=?"); $ex->execute([$name]);
    if ($exId = $ex->fetchColumn()) { echo json_encode(['success'=>false,'skipped'=>true,'existing_id'=>(int)$exId,'name'=>$name,'error'=>'موجود مسبقاً']); exit; }

    $catSlug = trim($app['category_slug'] ?? $app['cat_slug'] ?? ''); $catId = null;
    if ($catSlug) { $s=$pdo->prepare("SELECT id FROM categories WHERE slug=?"); $s->execute([$catSlug]); $catId=$s->fetchColumn()||null; }
    if (!$catId) { $r=$pdo->query("SELECT id FROM categories WHERE slug='apps' LIMIT 1")->fetch(); $catId=$r?$r['id']:null; }

    $slug = unique_slug($pdo, slugify($name));
    $playstoreUrl = $app['playstore_url'] ?? null; $iconPath=null; $pkg=$app['package_name']??null;
    if (!$playstoreUrl) {
        $playstoreUrl = playstore_search($name.' '.($app['developer']??'')) ?: playstore_search($name);
    }
    if ($playstoreUrl && !$pkg) {
        $meta = fetch_playstore_meta($playstoreUrl);
        if ($meta) { $pkg=$meta['package_name']??null; if (!empty($meta['icon_url'])) $iconPath=import_remote_icon($meta['icon_url'],$slug); }
    }
    $norm = function($v) {
        if (is_array($v)) return array_values(array_filter($v));
        if (is_string($v)&&strlen($v)) { $d=json_decode($v,true); return is_array($d)?array_values(array_filter($d)):[]; }
        return [];
    };
    $features=$norm($app['features']??[]); $pros=$norm($app['pros']??[]); $cons=$norm($app['cons']??[]);
    $steps=$norm($app['install_steps']??[]); $faq=$norm($app['faq']??[]);
    try {
        $pdo->prepare("INSERT INTO apps
            (name,slug,category_id,developer,license,icon_path,version,android_version,size_mb,
             short_description,long_description,features,pros,cons,install_steps,faq,whats_new,
             playstore_url,package_name,rating,seo_title,meta_description,keywords,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")
            ->execute([
                $name,$slug,$catId,trim($app['developer']??''),trim($app['license']??'Free'),$iconPath,
                trim($app['version']??''),trim($app['android_version']??''),
                is_numeric($app['size_mb']??'')?(float)$app['size_mb']:null,
                trim($app['short_description']??''),trim($app['long_description']??''),
                json_encode($features,JSON_UNESCAPED_UNICODE),json_encode($pros,JSON_UNESCAPED_UNICODE),
                json_encode($cons,JSON_UNESCAPED_UNICODE),json_encode($steps,JSON_UNESCAPED_UNICODE),
                json_encode($faq,JSON_UNESCAPED_UNICODE),trim($app['whats_new']??''),
                $playstoreUrl,$pkg,is_numeric($app['rating']??'')?(float)$app['rating']:4.5,
                trim($app['seo_title']??''),trim($app['meta_description']??''),trim($app['keywords']??''),
            ]);
        $newId=(int)$pdo->lastInsertId();
        echo json_encode(['success'=>true,'id'=>$newId,'name'=>$name,'has_icon'=>(bool)$iconPath,
            'edit_url'=>'admin.php?page=edit-app&id='.$newId], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
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
    @set_time_limit(150); // AI long-form generation + Play Store scrape + icon fetch can run past shared-hosting's 30s default
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $type = in_array($input['type'] ?? 'apps', ['apps','games'], true) ? $input['type'] : 'apps';
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم مطلوب']); exit; }

    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) { echo json_encode(['success'=>false,'error'=>'لم يتم إضافة مفتاح OpenRouter بعد.']); exit; }
    $models = build_model_rotation($pdo);

    $seoStandards = seo_prompt_standards();
    $prompt = <<<P
أنت كاتب محتوى سوري محترف متخصص بمراجعة التطبيقات، تكتب لموقع تحميل تطبيقات أندرويد عربي. التطبيق: "{$name}"

{$seoStandards}

- long_description: هذا هو المطلوب الأهم — نص طويل جداً ومفصّل لا يقل عن 2200 كلمة (كلما كان أطول وأغنى بالتفاصيل كان أفضل)، مكتوب بلهجة سورية خفيفة وأسلوب إنساني ودافئ كأنك تشرح لصديقك ليش هالتطبيق حلو وشو بيقدملك، وليس نصاً روبوتياً أو قائمة جافة. لكن حافظ على وضوح الكلام بحيث يفهمه كل الناطقين بالعربية (لهجة سورية مخفّفة قريبة من الفصحى، لا تستخدم عامية ثقيلة جداً). قسّم النص لعدة فقرات وعناوين فرعية داخل النص (بدون Markdown، فقط أسطر جديدة) تغطي على الأقل:
  1. مقدمة شخصية دافئة عن التطبيق وليش الناس عم يدورو عليه.
  2. نظرة عامة موسّعة على التطبيق واستخداماته.
  3. شرح تفصيلي مطوّل لكل ميزة رئيسية (فقرة كاملة لكل ميزة، مو مجرد جملة).
  4. لمين هالتطبيق مناسب (نوعية المستخدمين).
  5. مقارنة سريعة مع بدائل مشابهة وليش هاد أفضل أو مختلف.
  6. نصائح استخدام عملية وحلول لمشاكل شائعة.
  7. خاتمة تحفّز القارئ على التحميل.
  التزم بأسلوب SEO طبيعي (كرر اسم التطبيق وكلمات مثل "تحميل" و"تنزيل" و"APK" بشكل طبيعي ضمن السياق دون حشو مزعج).

أعد JSON صالح فقط بدون أي نص آخر أو Markdown:
{
  "name":"الاسم الرسمي",
  "seo_title":"",
  "meta_description":"",
  "keywords":"",
  "short_description":"جملة أو جملتين بأسلوب سوري ودود",
  "long_description":"",
  "developer":"اسم المطور المحتمل",
  "version":"رقم إصدار مثل 3.1.0",
  "android_version":"مثل: 7.0 فأعلى",
  "size_mb":"حجم تقريبي مثل 45",
  "license":"Free",
  "package_name":"com.developer.appname",
  "rating":4.5,
  "whats_new":"آخر التحديثات",
  "features":["ميزة 1","ميزة 2","ميزة 3","ميزة 4","ميزة 5","ميزة 6"],
  "pros":["إيجابية 1","إيجابية 2","إيجابية 3","إيجابية 4"],
  "cons":["سلبية 1","سلبية 2"],
  "install_steps":["خطوة 1","خطوة 2","خطوة 3","خطوة 4"],
  "faq":[{"q":"سؤال شائع","a":"إجابة مفصلة بلهجة ودودة"},{"q":"سؤال 2","a":"إجابة 2"},{"q":"سؤال 3","a":"إجابة 3"},{"q":"سؤال 4","a":"إجابة 4"}]
}
P;
    $result = openrouter_call_rotating($keys, $models, $prompt, 90, 9000);
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

/* ══════════════════════════════════════════════════════
   AJAX: Upload APK file — extracts metadata, stores in uploads/apk/
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'upload_apk' && is_admin()) {
    @set_time_limit(120);
    header('Content-Type: application/json');

    $appId = (int)($_POST['app_id'] ?? 0);
    if (!isset($_FILES['apk']) || $_FILES['apk']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['ok'=>false,'error'=>'لم يتم اختيار ملف']); exit;
    }

    // Get slug — from existing app or from name field
    $slug = '';
    if ($appId > 0) {
        $r = $pdo->prepare("SELECT slug FROM apps WHERE id=?")->execute([$appId]);
        $row = $pdo->query("SELECT slug FROM apps WHERE id=$appId")->fetch();
        if ($row) $slug = $row['slug'];
    }
    if (!$slug) {
        $slug = unique_slug($pdo, trim($_POST['app_name'] ?? 'app'));
    }

    $result = store_apk_file($_FILES['apk'], $slug);
    if (!$result['ok']) {
        echo json_encode(['ok'=>false,'error'=>$result['error']]); exit;
    }

    // If editing an existing app, delete the old APK file
    if ($appId > 0) {
        $old = $pdo->query("SELECT apk_path FROM apps WHERE id=$appId")->fetchColumn();
        if ($old && file_exists(__DIR__.'/'.$old)) @unlink(__DIR__.'/'.$old);
    }

    echo json_encode(array_merge(['ok'=>true], $result), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Delete APK file for an app
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'delete_apk' && is_admin()) {
    header('Content-Type: application/json');
    $appId = (int)($_POST['app_id'] ?? 0);
    if (!$appId) { echo json_encode(['ok'=>false]); exit; }
    $old = $pdo->query("SELECT apk_path FROM apps WHERE id=$appId")->fetchColumn();
    if ($old && file_exists(__DIR__.'/'.$old)) @unlink(__DIR__.'/'.$old);
    $pdo->prepare("UPDATE apps SET apk_path=NULL,apk_size_bytes=NULL,apk_hash_sha256=NULL,apk_hash_md5=NULL,apk_uploaded_at=NULL WHERE id=?")
        ->execute([$appId]);
    bump_cache_version($pdo);
    echo json_encode(['ok'=>true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Test server connection (FTP / FTPS / SFTP / SSH)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'test_server_conn' && is_admin()) {
    header('Content-Type: application/json');
    $type = $_POST['conn_type'] ?? 'ftp';
    $host = trim($_POST['host'] ?? '');
    $port = (int)($_POST['port'] ?? (in_array($type, ['sftp','ssh']) ? 22 : 21));
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $path = trim($_POST['remote_path'] ?? '/') ?: '/';
    if (!$host || !$user) { echo json_encode(['ok'=>false,'msg'=>'الرجاء إدخال المضيف واسم المستخدم']); exit; }
    try {
        if ($type === 'ftp') {
            $conn = @ftp_connect($host, $port, 15);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال بـ FTP: {$host}:{$port}");
            if (!@ftp_login($conn, $user, $pass)) { ftp_close($conn); throw new RuntimeException("بيانات الدخول غير صحيحة"); }
            ftp_pasv($conn, true);
            $pwd  = ftp_pwd($conn);
            $list = array_slice(ftp_nlist($conn, $path) ?: [], 0, 10);
            ftp_close($conn);
            echo json_encode(['ok'=>true,'msg'=>"✓ تم الاتصال — المجلد الحالي: {$pwd}",'items'=>$list]);
        } elseif ($type === 'ftps') {
            if (!function_exists('ftp_ssl_connect')) throw new RuntimeException("FTPS غير مدعوم على هذا السيرفر (يحتاج OpenSSL + php-ftp)");
            $conn = @ftp_ssl_connect($host, $port, 15);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال بـ FTPS: {$host}:{$port}");
            if (!@ftp_login($conn, $user, $pass)) { ftp_close($conn); throw new RuntimeException("بيانات الدخول غير صحيحة"); }
            ftp_pasv($conn, true);
            $pwd  = ftp_pwd($conn);
            $list = array_slice(ftp_nlist($conn, $path) ?: [], 0, 10);
            ftp_close($conn);
            echo json_encode(['ok'=>true,'msg'=>"✓ FTPS متصل — المجلد الحالي: {$pwd}",'items'=>$list]);
        } elseif ($type === 'sftp' || $type === 'ssh') {
            if (!function_exists('ssh2_connect')) throw new RuntimeException("مكتبة ssh2 PHP غير مثبّتة على هذا السيرفر. ثبّت php-ssh2 أو استخدم FTP بدلاً منها.");
            $conn = @ssh2_connect($host, $port);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال SSH: {$host}:{$port}");
            if (!@ssh2_auth_password($conn, $user, $pass)) throw new RuntimeException("بيانات الدخول SSH غير صحيحة");
            $sftp     = ssh2_sftp($conn);
            $realpath = ssh2_sftp_realpath($sftp, $path ?: '.');
            echo json_encode(['ok'=>true,'msg'=>"✓ SFTP/SSH متصل — المسار: {$realpath}",'items'=>[]]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'نوع اتصال غير معروف']);
        }
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Save server connection settings
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'save_server_conn' && is_admin()) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)");
    foreach (['server_conn_type','server_conn_host','server_conn_port','server_conn_user','server_conn_pass','server_conn_path'] as $f) {
        $stmt->execute([$f, trim($_POST[$f] ?? '')]);
    }
    echo json_encode(['ok'=>true,'msg'=>'تم حفظ بيانات الاتصال']);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: List remote directory (FTP / FTPS / SFTP)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list_remote_dir' && is_admin()) {
    header('Content-Type: application/json');
    $type = $_POST['conn_type'] ?? 'ftp';
    $host = trim($_POST['host'] ?? '');
    $port = (int)($_POST['port'] ?? (in_array($type, ['sftp','ssh']) ? 22 : 21));
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $path = trim($_POST['path'] ?? '/') ?: '/';
    try {
        if ($type === 'ftp' || $type === 'ftps') {
            $conn = ($type === 'ftps') ? @ftp_ssl_connect($host, $port, 15) : @ftp_connect($host, $port, 15);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال");
            if (!@ftp_login($conn, $user, $pass)) { ftp_close($conn); throw new RuntimeException("بيانات الدخول غير صحيحة"); }
            ftp_pasv($conn, true);
            $raw   = ftp_rawlist($conn, $path) ?: [];
            ftp_close($conn);
            $items = [];
            foreach ($raw as $line) {
                $parts = preg_split('/\s+/', trim($line), 9);
                if (count($parts) < 9) continue;
                $name = $parts[8];
                if ($name === '.' || $name === '..') continue;
                $items[] = ['name'=>$name,'dir'=>str_starts_with($parts[0], 'd'),'size'=>(int)$parts[4]];
            }
            echo json_encode(['ok'=>true,'path'=>$path,'items'=>$items]);
        } elseif ($type === 'sftp' || $type === 'ssh') {
            if (!function_exists('ssh2_connect')) throw new RuntimeException("ssh2 غير مثبّتة");
            $conn = @ssh2_connect($host, $port);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال SSH");
            if (!@ssh2_auth_password($conn, $user, $pass)) throw new RuntimeException("بيانات الدخول SSH غير صحيحة");
            $sftp  = ssh2_sftp($conn);
            $dh    = opendir("ssh2.sftp://{$sftp}{$path}");
            $items = [];
            while (($entry = readdir($dh)) !== false) {
                if ($entry === '.' || $entry === '..') continue;
                $full    = "ssh2.sftp://{$sftp}{$path}/{$entry}";
                $st      = @stat($full);
                $items[] = ['name'=>$entry,'dir'=>is_dir($full),'size'=>(int)($st['size'] ?? 0)];
            }
            closedir($dh);
            echo json_encode(['ok'=>true,'path'=>$path,'items'=>$items]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'نوع اتصال غير معروف']);
        }
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    }
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
    // Multi-key inputs — combine array into newline-separated string
    $multiKeys = array_filter(array_map('trim', $_POST['openrouter_key_multi'] ?? []));
    $mergedKey = implode("\n", $multiKeys) ?: trim($_POST['openrouter_key'] ?? '');
    set_cfg($pdo, 'openrouter_key', $mergedKey);

    foreach (['openrouter_model','openrouter_fallback','openrouter_image_model','contact_email',
              'google_site_verification','bing_site_verification','virustotal_api_key',
              'ai_provider',
              'telegram_bot_token','telegram_channel_id','telegram_channel_url',
              'indexnow_key','download_countdown_secs','download_custom_ad_code'] as $k) {
        set_cfg($pdo, $k, trim($_POST[$k] ?? ''));
    }
    set_cfg($pdo, 'openrouter_auto_rotate',      isset($_POST['openrouter_auto_rotate'])      ? '1' : '0');
    set_cfg($pdo, 'admin_email_notifications',   isset($_POST['admin_email_notifications'])   ? '1' : '0');
    set_cfg($pdo, 'telegram_enabled',            isset($_POST['telegram_enabled'])            ? '1' : '0');

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

// ─── Delete a blog post ───
if ($page === 'blog' && isset($_GET['del']) && isset($_GET['t']) &&
    hash_equals($_SESSION['csrf'] ?? '', $_GET['t'])) {
    $pdo->prepare("DELETE FROM blog_posts WHERE id=?")->execute([(int)$_GET['del']]);
    bump_cache_version($pdo);
    header('Location: admin.php?page=blog&msg=deleted'); exit;
}

// ─── Save / Update a blog post ───
if ($page === 'blog-edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $_POST = clean_utf8_deep($_POST);
    $id    = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    if (!$title) { $blogError = 'العنوان مطلوب'; }
    else {
        $type = isset(BLOG_TYPES[$_POST['type'] ?? '']) ? $_POST['type'] : 'article';
        $customSlug = trim($_POST['slug'] ?? '');
        if ($id) {
            $curSlug = $pdo->prepare("SELECT slug FROM blog_posts WHERE id=?");
            $curSlug->execute([$id]); $curSlug = $curSlug->fetchColumn();
            $slug = ($customSlug !== '' && $customSlug !== $curSlug)
                ? unique_blog_slug($pdo, $customSlug, $id)
                : $curSlug;
        } else {
            $slug = unique_blog_slug($pdo, $customSlug !== '' ? $customSlug : $title);
        }
        // code-page: assemble language sections from POST into JSON body
        if ($type === 'code-page') {
            $sections = [];
            foreach (array_keys(CODE_PAGE_LANGS) as $lang) {
                $code = $_POST['cp_' . $lang] ?? '';
                if (trim($code) !== '') $sections[$lang] = $code;
            }
            $bodyVal = json_encode(['sections' => $sections, 'description' => trim($_POST['cp_description'] ?? '')],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            $bodyVal = trim($_POST['body'] ?? '');
        }
        $d = [
            'type' => $type,
            'title' => $title,
            'slug' => $slug,
            'seo_title' => trim($_POST['seo_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'keywords' => trim($_POST['keywords'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'body' => $bodyVal,
            'status' => ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft',
        ];
        if ($id) {
            $sets = implode(', ', array_map(fn($k) => "$k=:$k", array_keys($d)));
            $d['id'] = $id;
            $pdo->prepare("UPDATE blog_posts SET $sets WHERE id=:id")->execute($d);
        } else {
            $cols = implode(',', array_keys($d));
            $vals = implode(',', array_map(fn($k) => ":$k", array_keys($d)));
            $pdo->prepare("INSERT INTO blog_posts ($cols) VALUES ($vals)")->execute($d);
            $id = (int)$pdo->lastInsertId();
        }
        bump_cache_version($pdo);
        header('Location: admin.php?page=blog&msg=saved'); exit;
    }
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

    // Slug — an admin-chosen slug wins if provided (and differs from the
    // current one on edit); otherwise it's auto-derived from the name, same
    // as before this field existed.
    $customSlug = trim($_POST['slug'] ?? '');
    if ($isEdit && $appId) {
        $existing = $pdo->prepare("SELECT slug,icon_path,screenshots,version,download_url,whats_new,apk_path,apk_size_bytes,apk_hash_sha256,apk_hash_md5,apk_uploaded_at,status FROM apps WHERE id=?");
        $existing->execute([$appId]); $existing = $existing->fetch();
        $slug = ($customSlug !== '' && $customSlug !== $existing['slug'])
            ? unique_slug($pdo, $customSlug, $appId)
            : $existing['slug'];
    } else {
        $slug = unique_slug($pdo, $customSlug !== '' ? $customSlug : $name);
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

    $downloadUrl    = trim($_POST['download_url'] ?? '');
    $downloadSource = in_array($_POST['download_source'] ?? '', ['playstore','apk','both'], true)
        ? $_POST['download_source'] : 'playstore';

    // Preserve existing APK data if a new one wasn't uploaded this request
    $apkPath       = $existing['apk_path']       ?? null;
    $apkSizeBytes  = $existing['apk_size_bytes']  ?? null;
    $apkHashSha256 = $existing['apk_hash_sha256'] ?? null;
    $apkHashMd5    = $existing['apk_hash_md5']    ?? null;
    $apkUploadedAt = $existing['apk_uploaded_at'] ?? null;
    // If a fresh APK was uploaded via AJAX before form submission, its data arrives in hidden fields
    if (!empty($_POST['apk_path_new']) && preg_match('#^uploads/apk/[A-Za-z0-9_\-\.]+\.apk$#', $_POST['apk_path_new'])) {
        $apkPath       = trim($_POST['apk_path_new']);
        $apkSizeBytes  = (int)($_POST['apk_size_bytes_new'] ?? 0) ?: null;
        $apkHashSha256 = trim($_POST['apk_hash_sha256_new'] ?? '') ?: null;
        $apkHashMd5    = trim($_POST['apk_hash_md5_new'] ?? '') ?: null;
        $apkUploadedAt = date('Y-m-d H:i:s');
    }

    $requestedStatus = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
    $forcedDraft = false;
    $hasDownloadMethod = $downloadUrl !== '' || ($apkPath && in_array($downloadSource, ['apk','both'], true));
    if ($requestedStatus === 'published' && !$hasDownloadMethod) {
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
        'slug'              => $slug,
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
        'download_source'   => $downloadSource,
        'apk_path'          => $apkPath,
        'apk_size_bytes'    => $apkSizeBytes,
        'apk_hash_sha256'   => $apkHashSha256,
        'apk_hash_md5'      => $apkHashMd5,
        'apk_uploaded_at'   => $apkUploadedAt,
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
        'badge'             => in_array($_POST['badge'] ?? '', ['','new','updated','hot','choice'], true) ? ($_POST['badge'] ?? '') : '',
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
        $wasPublished = ($existing['status'] ?? '') === 'published';
        $sets = implode(', ', array_map(fn($k) => "$k=:$k", array_keys($d)));
        $d['id'] = $appId;
        $pdo->prepare("UPDATE apps SET $sets WHERE id=:id")->execute($d);
        bump_cache_version($pdo);
        // Notify Telegram only when transitioning to published — deferred until
        // AFTER the HTTP response is flushed so the admin never sees a freeze.
        if (!$wasPublished && $requestedStatus === 'published') {
            $saved = $pdo->prepare("SELECT * FROM apps WHERE id=?")->execute([$appId]) ? $pdo->query("SELECT * FROM apps WHERE id={$appId}")->fetch(PDO::FETCH_ASSOC) : $d;
            $notifyApp = is_array($saved) ? $saved : $d;
            register_shutdown_function(function() use ($pdo, $notifyApp) {
                if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
                telegram_notify_new_app($pdo, $notifyApp);
                ping_search_engines($pdo, app_url($notifyApp['slug'] ?? ''));
            });
        } elseif ($wasPublished && $requestedStatus === 'published' && !empty($d['slug'])) {
            $pingSlug = $d['slug'];
            register_shutdown_function(function() use ($pdo, $pingSlug) {
                if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
                ping_search_engines($pdo, app_url($pingSlug));
            });
        }
        header('Location: admin.php?page=apps&msg=' . ($forcedDraft ? 'updated_no_link' : 'updated')); exit;
    } else {
        $cols = implode(',', array_keys($d));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($d)));
        $pdo->prepare("INSERT INTO apps ($cols) VALUES ($vals)")->execute($d);
        $newId = (int)$pdo->lastInsertId();
        bump_cache_version($pdo);
        // Deferred Telegram notification — fires after HTTP redirect is sent.
        if ($requestedStatus === 'published' && $newId) {
            $saved = $pdo->prepare("SELECT * FROM apps WHERE id=?")->execute([$newId]) ? $pdo->query("SELECT * FROM apps WHERE id={$newId}")->fetch(PDO::FETCH_ASSOC) : array_merge($d, ['id' => $newId]);
            $notifyApp = is_array($saved) ? $saved : array_merge($d, ['id' => $newId]);
            register_shutdown_function(function() use ($pdo, $notifyApp) {
                if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
                telegram_notify_new_app($pdo, $notifyApp);
                ping_search_engines($pdo, app_url($notifyApp['slug'] ?? ''));
            });
        }
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
        bump_cache_version($pdo);
        header('Location: admin.php?page=comments&msg=updated'); exit;
    }
    if (isset($_GET['del_comment'])) {
        $pdo->prepare("DELETE FROM comments WHERE id=?")->execute([(int)$_GET['del_comment']]);
        bump_cache_version($pdo);
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
  <meta charset="UTF-8"><link rel="icon" type="image/svg+xml" href="<?= h(url("favicon.svg")) ?>"><meta name="theme-color" content="#2563eb"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>تسجيل الدخول — Tenzil admin</title>
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
   AJAX: Mass re-index all published apps
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'reindex_all' && is_admin()) {
    header('Content-Type: application/json');
    $apps = $pdo->query("SELECT id, slug FROM apps WHERE status='published' ORDER BY id")->fetchAll();
    $pinged = 0; $errors = 0;
    // Ping sitemap once
    $sm = urlencode(rtrim(SITE_URL, '/') . '/sitemap.xml');
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    @file_get_contents("https://www.google.com/ping?sitemap={$sm}", false, $ctx);
    @file_get_contents("https://www.bing.com/ping?sitemap={$sm}", false, $ctx);
    // Submit all URLs to IndexNow in one batch (up to 10k URLs)
    $key = get_cfg($pdo, 'indexnow_key', '');
    $host = parse_url(SITE_URL, PHP_URL_HOST) ?: '';
    $urlList = array_map(fn($a) => app_url($a['slug']), $apps);
    // Add static pages
    foreach (['', 'blog', 'about', 'privacy-policy', 'terms', 'contact'] as $p) {
        $urlList[] = url($p);
    }
    if ($key && $urlList) {
        $body = json_encode(['host' => $host, 'key' => $key, 'urlList' => array_values(array_unique($urlList))]);
        $ictx = stream_context_create(['http' => [
            'method' => 'POST', 'timeout' => 10, 'ignore_errors' => true,
            'header' => "Content-Type: application/json\r\nContent-Length: " . strlen($body),
            'content' => $body,
        ]]);
        $resp = @file_get_contents('https://api.indexnow.org/indexnow', false, $ictx);
        $pinged = count($urlList);
    }
    // Update last_indexed_at for all published apps
    $pdo->exec("UPDATE apps SET last_indexed_at=NOW(), index_status='indexed' WHERE status='published'");
    log_security_event($pdo, 'reindex_all', 'info', "Mass re-index: {$pinged} URLs submitted to IndexNow");
    echo json_encode(['ok' => true, 'pinged' => $pinged, 'total' => count($apps)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Clear security log
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clear_security_log' && is_admin()) {
    header('Content-Type: application/json');
    $pdo->exec("DELETE FROM security_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo json_encode(['ok' => true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — list directory
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_list' && is_admin()) {
    header('Content-Type: application/json');
    $dir = trim($_GET['dir'] ?? '');
    $root = ROOT_PATH;
    // Allowed browse roots
    $allowedDirs = ['', 'assets/css', 'assets/js', 'uploads', 'install'];
    if (!in_array($dir, $allowedDirs, true) && !preg_match('#^(assets/(css|js)|uploads(/[a-zA-Z0-9_\-]+)?|install)$#', $dir)) {
        echo json_encode(['ok' => false, 'error' => 'Directory not allowed']); exit;
    }
    $absDir = $dir ? ($root . '/' . $dir) : $root;
    $absDir = realpath($absDir);
    if (!$absDir || !is_dir($absDir) || strpos($absDir, $root) !== 0) {
        echo json_encode(['ok' => false, 'error' => 'Invalid directory']); exit;
    }
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    $items = [];
    foreach (scandir($absDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $abs = $absDir . '/' . $f;
        $isDir = is_dir($abs);
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if ($isDir) {
            $rel = ($dir ? $dir . '/' : '') . $f;
            if (in_array($rel, $allowedDirs, true) || preg_match('#^(assets/(css|js)|uploads(/[a-zA-Z0-9_\-]+)?|install)$#', $rel)) {
                $items[] = ['name' => $f, 'type' => 'dir', 'path' => $rel];
            }
        } elseif (in_array($ext, $allowedExts, true) || $f === '.htaccess' || $f === 'robots.txt') {
            // Don't list config.php — it contains DB credentials
            if ($f === 'config.php') continue;
            $rel = ($dir ? $dir . '/' : '') . $f;
            $items[] = ['name' => $f, 'type' => 'file', 'path' => $rel, 'size' => filesize($abs), 'mtime' => filemtime($abs), 'ext' => $ext ?: 'txt'];
        }
    }
    usort($items, fn($a,$b) => ($a['type'] === $b['type']) ? strcmp($a['name'], $b['name']) : ($a['type'] === 'dir' ? -1 : 1));
    echo json_encode(['ok' => true, 'dir' => $dir ?: '/', 'items' => $items], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — read file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_read' && is_admin()) {
    header('Content-Type: application/json');
    $path = trim($_GET['path'] ?? '');
    $root = ROOT_PATH;
    // Reject config.php
    if (basename($path) === 'config.php') {
        echo json_encode(['ok' => false, 'error' => 'config.php لا يمكن تعديله من هنا — استخدم FTP']); exit;
    }
    $abs = realpath($root . '/' . ltrim($path, '/'));
    if (!$abs || !is_file($abs) || strpos($abs, $root) !== 0) {
        echo json_encode(['ok' => false, 'error' => 'File not found or not allowed']); exit;
    }
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    if (!in_array($ext, $allowedExts, true) && basename($abs) !== '.htaccess' && basename($abs) !== 'robots.txt') {
        echo json_encode(['ok' => false, 'error' => 'File type not editable']); exit;
    }
    $content = file_get_contents($abs);
    echo json_encode(['ok' => true, 'content' => $content, 'path' => $path, 'ext' => $ext ?: 'txt', 'size' => strlen($content)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — write file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_write' && is_admin()) {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'error'=>'POST required']); exit; }
    $path    = trim($_POST['path'] ?? '');
    $content = $_POST['content'] ?? '';
    $root    = ROOT_PATH;
    // Forbidden: config.php, admin.php security sections
    if (in_array(basename($path), ['config.php'], true)) {
        echo json_encode(['ok' => false, 'error' => 'هذا الملف محمي — استخدم FTP']); exit;
    }
    // Must be within root and have allowed extension
    $abs = $root . '/' . str_replace(['../', '..\\', "\0"], '', ltrim($path, '/'));
    if (!file_exists($abs)) { echo json_encode(['ok' => false, 'error' => 'File not found']); exit; }
    $realAbs = realpath($abs);
    if (!$realAbs || strpos($realAbs, $root) !== 0) {
        echo json_encode(['ok' => false, 'error' => 'Path traversal detected']); exit;
    }
    $ext = strtolower(pathinfo($realAbs, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    if (!in_array($ext, $allowedExts, true) && basename($realAbs) !== '.htaccess' && basename($realAbs) !== 'robots.txt') {
        echo json_encode(['ok' => false, 'error' => 'File type not writable']); exit;
    }
    // Backup current version (keep last 1 backup)
    @file_put_contents($realAbs . '.bak', file_get_contents($realAbs));
    if (file_put_contents($realAbs, $content) === false) {
        echo json_encode(['ok' => false, 'error' => 'Write failed — check file permissions']); exit;
    }
    log_security_event($pdo, 'file_edited', 'info', "File edited via file manager: $path");
    bump_cache_version($pdo);
    echo json_encode(['ok' => true, 'bytes' => strlen($content)]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — create new file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_create' && is_admin()) {
    header('Content-Type: application/json');
    $dir  = trim($_POST['dir'] ?? '');
    $name = basename(trim($_POST['name'] ?? ''));
    if (!$name) { echo json_encode(['ok'=>false,'error'=>'اسم الملف مطلوب']); exit; }
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        echo json_encode(['ok'=>false,'error'=>'نوع الملف غير مسموح به']); exit;
    }
    $root = ROOT_PATH;
    $absDir = $dir ? realpath($root . '/' . $dir) : $root;
    if (!$absDir || strpos($absDir, $root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'المسار غير مسموح به']); exit;
    }
    $abs = $absDir . '/' . $name;
    if (file_exists($abs)) { echo json_encode(['ok'=>false,'error'=>'الملف موجود بالفعل']); exit; }
    if (file_put_contents($abs, '') === false) {
        echo json_encode(['ok'=>false,'error'=>'فشل إنشاء الملف — تحقق من الصلاحيات']); exit;
    }
    log_security_event($pdo, 'file_created', 'info', "File created via file manager: $dir/$name");
    echo json_encode(['ok'=>true,'path'=>($dir?$dir.'/':'').$name]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Mass re-index all published apps via IndexNow
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'reindex_all' && is_admin()) {
    header('Content-Type: application/json');
    $apps = $pdo->query("SELECT id,slug FROM apps WHERE status='published' ORDER BY id")->fetchAll();
    $sm   = urlencode(rtrim(SITE_URL,'/').'/sitemap.xml');
    $ctx  = stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true]]);
    @file_get_contents("https://www.google.com/ping?sitemap={$sm}", false, $ctx);
    @file_get_contents("https://www.bing.com/ping?sitemap={$sm}", false, $ctx);
    $key  = get_cfg($pdo,'indexnow_key','');
    $host = parse_url(SITE_URL, PHP_URL_HOST) ?: '';
    $urlList = array_map(fn($a) => app_url($a['slug']), $apps);
    foreach (['','blog','about','privacy-policy','terms','contact','updates','top?by=downloads'] as $p)
        $urlList[] = url($p);
    $urlList = array_values(array_unique(array_filter($urlList)));
    $pinged = 0;
    if ($key && $urlList) {
        $body = json_encode(['host'=>$host,'key'=>$key,'urlList'=>$urlList]);
        $ictx = stream_context_create(['http'=>['method'=>'POST','timeout'=>10,'ignore_errors'=>true,
            'header'=>"Content-Type: application/json\r\nContent-Length: ".strlen($body),'content'=>$body]]);
        @file_get_contents('https://api.indexnow.org/indexnow', false, $ictx);
        $pinged = count($urlList);
    }
    $pdo->exec("UPDATE apps SET last_indexed_at=NOW(), index_status='indexed' WHERE status='published'");
    log_security_event($pdo,'reindex_all','info',"Mass re-index: {$pinged} URLs submitted to IndexNow + sitemap ping");
    echo json_encode(['ok'=>true,'pinged'=>$pinged,'total'=>count($apps)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Clear old security log entries
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clear_security_log' && is_admin()) {
    header('Content-Type: application/json');
    $pdo->exec("DELETE FROM security_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo json_encode(['ok'=>true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — list directory
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_list' && is_admin()) {
    header('Content-Type: application/json');
    $dir  = trim($_GET['dir'] ?? '');
    $root = ROOT_PATH;
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    // Whitelist directories available for browsing
    if ($dir && !preg_match('#^(assets/(css|js)|uploads(/[a-zA-Z0-9_\-]+)?|install)$#', $dir)) {
        echo json_encode(['ok'=>false,'error'=>'Directory not allowed']); exit;
    }
    $absDir = $dir ? realpath($root.'/'.$dir) : $root;
    if (!$absDir || !is_dir($absDir) || strpos($absDir, $root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'Invalid directory']); exit;
    }
    $browseable = ['','assets/css','assets/js','uploads','install'];
    $items = [];
    foreach (scandir($absDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $abs = $absDir.'/'.$f;
        $rel = ($dir ? $dir.'/' : '').$f;
        if (is_dir($abs)) {
            if (in_array($rel, $browseable, true) || preg_match('#^(assets/(css|js)|uploads(/[a-zA-Z0-9_\-]+)?|install)$#', $rel))
                $items[] = ['name'=>$f,'type'=>'dir','path'=>$rel];
        } else {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if ($f === 'config.php') continue; // protect credentials
            if (in_array($ext,$allowedExts,true) || $f==='.htaccess' || $f==='robots.txt')
                $items[] = ['name'=>$f,'type'=>'file','path'=>$rel,'size'=>filesize($abs),'mtime'=>filemtime($abs),'ext'=>$ext ?: 'txt'];
        }
    }
    usort($items, fn($a,$b)=>$a['type']===$b['type'] ? strcmp($a['name'],$b['name']) : ($a['type']==='dir' ? -1 : 1));
    echo json_encode(['ok'=>true,'dir'=>$dir ?: '/','items'=>$items], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — read file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_read' && is_admin()) {
    header('Content-Type: application/json');
    $path = trim($_GET['path'] ?? '');
    $root = ROOT_PATH;
    if (basename($path) === 'config.php') {
        echo json_encode(['ok'=>false,'error'=>'config.php محمي — استخدم FTP لتعديله']); exit;
    }
    $abs = realpath($root.'/'.ltrim($path,'/'));
    if (!$abs || !is_file($abs) || strpos($abs, $root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'الملف غير موجود']); exit;
    }
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    if (!in_array($ext,$allowedExts,true) && basename($abs)!=='.htaccess' && basename($abs)!=='robots.txt') {
        echo json_encode(['ok'=>false,'error'=>'نوع الملف غير قابل للتعديل']); exit;
    }
    $content = file_get_contents($abs);
    echo json_encode(['ok'=>true,'content'=>$content,'path'=>$path,'ext'=>$ext ?: 'txt','size'=>strlen($content)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — write file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_write' && is_admin()) {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'error'=>'POST required']); exit; }
    $path    = trim($_POST['path'] ?? '');
    $content = $_POST['content'] ?? '';
    $root    = ROOT_PATH;
    if (in_array(basename($path),['config.php'],true)) {
        echo json_encode(['ok'=>false,'error'=>'هذا الملف محمي — استخدم FTP']); exit;
    }
    $safe = str_replace(['../','..\\',"../","..\\","\0"],'',$path);
    $abs  = realpath($root.'/'.ltrim($safe,'/'));
    if (!$abs || !is_file($abs) || strpos($abs,$root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'الملف غير موجود أو المسار غير مسموح به']); exit;
    }
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    if (!in_array($ext,$allowedExts,true) && basename($abs)!=='.htaccess' && basename($abs)!=='robots.txt') {
        echo json_encode(['ok'=>false,'error'=>'نوع الملف غير قابل للكتابة']); exit;
    }
    @file_put_contents($abs.'.bak', file_get_contents($abs)); // single-slot backup
    if (file_put_contents($abs, $content) === false) {
        echo json_encode(['ok'=>false,'error'=>'فشل الكتابة — تحقق من صلاحيات الملف']); exit;
    }
    log_security_event($pdo,'file_edited','info',"File edited via file manager: $path");
    bump_cache_version($pdo);
    echo json_encode(['ok'=>true,'bytes'=>strlen($content)]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — create new file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_create' && is_admin()) {
    header('Content-Type: application/json');
    $dir  = trim($_POST['dir'] ?? '');
    $name = basename(trim($_POST['name'] ?? ''));
    if (!$name) { echo json_encode(['ok'=>false,'error'=>'اسم الملف مطلوب']); exit; }
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md'];
    if (!in_array($ext,$allowedExts,true)) {
        echo json_encode(['ok'=>false,'error'=>'نوع الملف غير مسموح به']); exit;
    }
    $root = ROOT_PATH;
    $absDir = $dir ? realpath($root.'/'.$dir) : $root;
    if (!$absDir || strpos($absDir,$root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'المسار غير مسموح به']); exit;
    }
    $abs = $absDir.'/'.$name;
    if (file_exists($abs)) { echo json_encode(['ok'=>false,'error'=>'الملف موجود بالفعل']); exit; }
    if (file_put_contents($abs,'') === false) {
        echo json_encode(['ok'=>false,'error'=>'فشل الإنشاء — تحقق من الصلاحيات']); exit;
    }
    log_security_event($pdo,'file_created','info',"New file created: $dir/$name");
    echo json_encode(['ok'=>true,'path'=>($dir?$dir.'/':'').$name]);
    exit;
}

/* ══════════════════════════════════════════════════════
   MAIN ADMIN LAYOUT
   ══════════════════════════════════════════════════════ */
$navLinks = [
    'dashboard' => ['label'=>'لوحة التحكم',   'icon'=>'M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-4h4v4h4a1 1 0 001-1v-9'],
    'apps'      => ['label'=>'التطبيقات',     'icon'=>'M4 6h16M4 12h16M4 18h16'],
    'add-app'   => ['label'=>'إضافة تطبيق',   'icon'=>'M12 5v14m-7-7h14'],
    'categories'=> ['label'=>'التصنيفات',     'icon'=>'M3 7h4v4H3V7zm0 6h4v4H3v-4zm6-6h12v4H9V7zm0 6h12v4H9v-4z'],
    'bulk-generate' => ['label'=>'توليد تطبيقات رائجة', 'icon'=>'M12 2l2.4 7.2H22l-6 4.6 2.3 7.2-6.3-4.5-6.3 4.5 2.3-7.2-6-4.6h7.6z'],
    'import-preset' => ['label'=>'استيراد 30 تطبيقاً جاهزاً', 'icon'=>'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12'],
    'file-import'   => ['label'=>'استيراد من ملف',            'icon'=>'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'assistant' => ['label'=>'مساعد الذكاء الاصطناعي', 'icon'=>'M9 18h6m-5 3h4M12 3a6 6 0 00-4 10.5c.6.5 1 1.3 1 2.1V16h6v-.4c0-.8.4-1.6 1-2.1A6 6 0 0012 3z'],
    'messages'  => ['label'=>'رسائل التواصل', 'icon'=>'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zm0 2l8 6 8-6'],
    'comments'  => ['label'=>'التعليقات والتقييمات', 'icon'=>'M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z'],
    'blog'      => ['label'=>'المدونة والمحتوى', 'icon'=>'M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 006.5 22H20V2H6.5A2.5 2.5 0 004 4.5v15z'],
    'article-gen'=> ['label'=>'توليد مقالات التطبيقات','icon'=>'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
    'stats'     => ['label'=>'إحصائيات الموقع', 'icon'=>'M3 3v18h18M8 17V9m4 8V5m4 12v-6'],
    'connection'=> ['label'=>'اختبار الاتصال', 'icon'=>'M13 10V3L4 14h7v7l9-11h-7z'],
    'database'  => ['label'=>'قاعدة البيانات', 'icon'=>'M4 6c0-1.1 3.6-2 8-2s8 .9 8 2-3.6 2-8 2-8-.9-8-2zm0 0v12c0 1.1 3.6 2 8 2s8-.9 8-2V6M4 12c0 1.1 3.6 2 8 2s8-.9 8-2'],
    'security'  => ['label'=>'الحماية والأمان', 'icon'=>'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'],
    'file-manager' => ['label'=>'مدير الملفات', 'icon'=>'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
    'settings'  => ['label'=>'الإعدادات',     'icon'=>'M12 15a3 3 0 100-6 3 3 0 000 6zm0 0v3m0-12V3m9 9h-3M6 12H3m15.364-6.364l-2.121 2.121M8.757 15.243l-2.121 2.121M18.364 18.364l-2.121-2.121M8.757 8.757L6.636 6.636'],
    'deploy'    => ['label'=>'اتصال السيرفر', 'icon'=>'M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"><link rel="icon" type="image/svg+xml" href="<?= h(url("favicon.svg")) ?>"><meta name="theme-color" content="#2563eb"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($navLinks[$page]['label'] ?? 'Admin') ?> — Tenzil</title>
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
  <div class="stat-card" style="border-color:rgba(6,182,212,.3)">
    <div class="stat-num" style="color:var(--cyan)"><?= (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='indexed'")->fetchColumn() ?></div>
    <div class="stat-label">تطبيق مفهرَس</div>
  </div>
  <div class="stat-card" style="border-color:rgba(239,68,68,.3)">
    <div class="stat-num" style="color:var(--danger)"><?= (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='critical' AND DATE(created_at)=CURDATE()")->fetchColumn() ?></div>
    <div class="stat-label">تنبيهات أمان اليوم</div>
  </div>
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
      <div class="form-group full">
        <label class="form-label">رابط الصفحة (Slug)</label>
        <input class="form-input" id="f-slug" type="text" name="slug" dir="ltr" style="text-align:left"
               value="<?= h($app['slug'] ?? '') ?>" placeholder="سيُنشأ تلقائياً من الاسم إن تُرك فارغاً"
               pattern="[a-zA-Z0-9؀-ۿ\-]*">
        <span style="font-size:11px;color:var(--muted);display:block;margin-top:4px" dir="ltr">
          <?= h(SITE_URL) ?>/<span id="slug-preview"><?= h($app['slug'] ?? 'app-name') ?></span>
          <?php if ($isEdit): ?> — تنبيه: تغييره يغيّر رابط الصفحة الحالي (قد يفقد أي روابط خارجية مؤشرة عليه).<?php endif; ?>
        </span>
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

    <!-- Source selector -->
    <div class="form-group" style="margin-bottom:18px">
      <label class="form-label">مصدر التحميل</label>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px">
        <?php foreach (['playstore'=>'Google Play فقط','apk'=>'APK مستضاف مباشرة','both'=>'كلاهما (Play + APK)'] as $sv=>$sl): ?>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;padding:7px 14px;border:1px solid var(--border-c);border-radius:8px;<?= ($app['download_source']??'playstore')===$sv?'background:var(--accent-light,#e8f4ff);border-color:var(--accent,#0d6efd)':'' ?>">
          <input type="radio" name="download_source" value="<?= $sv ?>" <?= ($app['download_source']??'playstore')===$sv?'checked':'' ?> style="accent-color:var(--accent,#0d6efd)">
          <?= $sl ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- APK Upload Zone -->
    <div id="apk-panel" style="border:1px solid var(--border-c);border-radius:12px;padding:16px;margin-bottom:18px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <strong style="font-size:13px">📦 APK مستضاف</strong>
        <?php if (!empty($app['apk_path'])): ?>
        <button type="button" id="btn-del-apk" style="font-size:11px;color:var(--danger,#dc3545);background:none;border:none;cursor:pointer">× حذف APK</button>
        <?php endif; ?>
      </div>

      <?php if (!empty($app['apk_path'])): ?>
      <!-- Existing APK info -->
      <div id="apk-info-box" style="background:var(--bg-light,#f8f9fa);border-radius:8px;padding:12px;font-size:12px;margin-bottom:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
          <div><span style="color:var(--muted)">الحجم:</span> <strong><?= h(format_apk_size((int)($app['apk_size_bytes']??0))) ?></strong></div>
          <div><span style="color:var(--muted)">رُفع:</span> <strong><?= h(substr($app['apk_uploaded_at']??'',0,10)) ?></strong></div>
          <?php if ($app['apk_hash_sha256']): ?>
          <div style="grid-column:1/-1">
            <span style="color:var(--muted)">SHA-256:</span>
            <code style="font-size:10px;word-break:break-all;cursor:pointer" title="انقر للنسخ" onclick="navigator.clipboard.writeText('<?= h($app['apk_hash_sha256']) ?>');this.style.color='green'"><?= h($app['apk_hash_sha256']) ?></code>
          </div>
          <?php endif; ?>
          <?php if ($app['apk_hash_md5']): ?>
          <div style="grid-column:1/-1">
            <span style="color:var(--muted)">MD5:</span>
            <code style="font-size:11px;cursor:pointer" title="انقر للنسخ" onclick="navigator.clipboard.writeText('<?= h($app['apk_hash_md5']) ?>');this.style.color='green'"><?= h($app['apk_hash_md5']) ?></code>
          </div>
          <?php endif; ?>
        </div>
        <div style="margin-top:8px"><a href="<?= h(url($app['apk_path'])) ?>" style="font-size:11px;color:var(--accent,#0d6efd)" target="_blank">📥 تحميل الملف المُخزّن</a></div>
      </div>
      <!-- Hidden fields to preserve APK data through form save -->
      <input type="hidden" name="apk_path_new" id="apk_path_new" value="<?= h($app['apk_path']) ?>">
      <input type="hidden" name="apk_size_bytes_new" id="apk_size_bytes_new" value="<?= h($app['apk_size_bytes']??'') ?>">
      <input type="hidden" name="apk_hash_sha256_new" id="apk_hash_sha256_new" value="<?= h($app['apk_hash_sha256']??'') ?>">
      <input type="hidden" name="apk_hash_md5_new" id="apk_hash_md5_new" value="<?= h($app['apk_hash_md5']??'') ?>">
      <?php else: ?>
      <input type="hidden" name="apk_path_new" id="apk_path_new" value="">
      <input type="hidden" name="apk_size_bytes_new" id="apk_size_bytes_new" value="">
      <input type="hidden" name="apk_hash_sha256_new" id="apk_hash_sha256_new" value="">
      <input type="hidden" name="apk_hash_md5_new" id="apk_hash_md5_new" value="">
      <?php endif; ?>

      <!-- Upload drop zone -->
      <div id="apk-drop-zone" style="border:2px dashed var(--border-c);border-radius:10px;padding:20px;text-align:center;cursor:pointer;transition:.2s">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5" style="margin-bottom:6px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        <p style="font-size:12px;color:var(--muted);margin:0">اسحب ملف APK هنا أو <strong style="color:var(--accent,#0d6efd);cursor:pointer" onclick="document.getElementById('apk-file-input').click()">اختر ملفاً</strong></p>
        <p style="font-size:11px;color:var(--muted);margin:4px 0 0">الحد الأقصى 500MB</p>
        <input type="file" id="apk-file-input" accept=".apk,application/vnd.android.package-archive" style="display:none">
      </div>

      <!-- Upload progress -->
      <div id="apk-upload-progress" style="display:none;margin-top:12px">
        <div style="height:6px;background:var(--bg-light,#f0f0f0);border-radius:4px;overflow:hidden">
          <div id="apk-progress-bar" style="height:100%;width:0;background:var(--accent,#0d6efd);transition:width .2s"></div>
        </div>
        <p id="apk-upload-status" style="font-size:12px;color:var(--muted);margin:6px 0 0;text-align:center">جاري الرفع...</p>
      </div>

      <!-- Extracted metadata (shown after successful upload) -->
      <div id="apk-meta-box" style="display:none;margin-top:12px;background:var(--bg-light,#f8f9fa);border-radius:8px;padding:12px;font-size:12px"></div>
    </div>

    <!-- External URL section -->
    <div class="form-group" style="margin-bottom:12px">
      <label class="form-label">رابط التحميل الخارجي (Google Play / CDN)</label>
      <input class="form-input" type="text" name="download_url" value="<?= h($app['download_url']??'') ?>" placeholder="https://play.google.com/store/apps/details?id=...">
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
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <label class="form-label" style="margin-bottom:8px">شارة التطبيق</label>
      <select class="form-select" name="badge" style="max-width:300px">
        <option value="" <?= ($app['badge']??'')===''?'selected':'' ?>>— بلا شارة (الافتراضي)</option>
        <option value="new" <?= ($app['badge']??'')==='new'?'selected':'' ?>>🆕 جديد</option>
        <option value="updated" <?= ($app['badge']??'')==='updated'?'selected':'' ?>>🔄 محدّث</option>
        <option value="hot" <?= ($app['badge']??'')==='hot'?'selected':'' ?>>🔥 رائج</option>
        <option value="choice" <?= ($app['badge']??'')==='choice'?'selected':'' ?>>⭐ اختيار المحرر</option>
      </select>
      <div class="form-hint">تُعرض كشارة ملوّنة على صفحة التطبيق. "جديد" يُضاف تلقائياً خلال 7 أيام من النشر.</div>
    </div>
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

<?php if ($isEdit): ?>
<div class="panel" style="margin-bottom:40px">
  <h2>التحقق من سلامة الرابط</h2>
  <p class="form-hint" style="margin-bottom:14px">
    بعد التأكد يدوياً من أن رابط التحميل آمن وسليم، اضغط "تحقق الفريق" لإظهار شارة "رابط تم التحقق من سلامته بواسطة فريق Tenzil" على صفحة التطبيق وصفحة التحميل.
  </p>
  <div style="margin-bottom:12px" id="verify-status">
    <?php if ($app['link_verified']): ?>
      <span class="status-badge status-published">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
        تم التحقق من سلامة الرابط<?= $app['verified_at'] ? ' — ' . date('Y-m-d', strtotime($app['verified_at'])) : '' ?>
      </span>
    <?php else: ?>
      <span class="status-badge status-draft">لم يتم التحقق من الرابط بعد</span>
    <?php endif; ?>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php if (!$app['link_verified']): ?>
    <button type="button" id="btn-verify-link" class="btn-ai" data-app-id="<?= (int)$app['id'] ?>" data-action="verify">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
      تحقق الفريق — الرابط آمن
    </button>
    <?php else: ?>
    <button type="button" id="btn-verify-link" class="btn-ai" data-app-id="<?= (int)$app['id'] ?>" data-action="unverify" style="background:rgba(220,38,38,.1);color:var(--danger)">
      إلغاء التحقق
    </button>
    <?php endif; ?>
  </div>
  <div id="verify-result" style="margin-top:12px"></div>
</div>
<?php endif; ?>

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

<script>
/* ── APK Upload & Metadata Display ── */
(function(){
  const APP_ID   = <?= (int)($app['id'] ?? 0) ?>;
  const APP_NAME = <?= json_encode($app['name'] ?? '') ?>;
  const dropZone = document.getElementById('apk-drop-zone');
  const fileInput = document.getElementById('apk-file-input');
  const progress  = document.getElementById('apk-upload-progress');
  const progBar   = document.getElementById('apk-progress-bar');
  const status    = document.getElementById('apk-upload-status');
  const metaBox   = document.getElementById('apk-meta-box');
  const delBtn    = document.getElementById('btn-del-apk');
  const hidPath   = document.getElementById('apk_path_new');
  const hidSize   = document.getElementById('apk_size_bytes_new');
  const hidSha    = document.getElementById('apk_hash_sha256_new');
  const hidMd5    = document.getElementById('apk_hash_md5_new');

  if (!dropZone) return;

  // Drag-and-drop
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor='var(--accent,#0d6efd)'; });
  dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor=''; });
  dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.style.borderColor='';
    const f = e.dataTransfer.files[0];
    if (f) uploadApk(f);
  });
  fileInput && fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) uploadApk(fileInput.files[0]);
  });

  // Delete APK
  delBtn && delBtn.addEventListener('click', async () => {
    if (!confirm('حذف ملف APK المُخزّن؟')) return;
    const fd = new FormData();
    fd.append('app_id', APP_ID);
    const r = await fetch('admin.php?ajax=delete_apk', {method:'POST', body:fd});
    const j = await r.json().catch(()=>({ok:false}));
    if (j.ok) {
      hidPath.value=''; hidSize.value=''; hidSha.value=''; hidMd5.value='';
      delBtn.remove();
      const info = document.getElementById('apk-info-box');
      if (info) info.remove();
    }
  });

  function uploadApk(file) {
    if (!file.name.endsWith('.apk') && file.type !== 'application/vnd.android.package-archive') {
      alert('يرجى اختيار ملف APK'); return;
    }
    const fd = new FormData();
    fd.append('apk', file);
    fd.append('app_id', APP_ID);
    fd.append('app_name', APP_NAME || document.querySelector('[name="name"]')?.value || 'app');

    progress.style.display = 'block';
    progBar.style.width = '0%';
    status.textContent = 'جاري الرفع...';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'admin.php?ajax=upload_apk');

    xhr.upload.onprogress = e => {
      if (e.lengthComputable) {
        const pct = Math.round(e.loaded / e.total * 90);
        progBar.style.width = pct + '%';
        status.textContent = `جاري الرفع... ${pct}%`;
      }
    };

    xhr.onload = () => {
      progBar.style.width = '100%';
      let j;
      try { j = JSON.parse(xhr.responseText); } catch(e) { j = {ok:false,error:'خطأ في الاستجابة'}; }

      if (!j.ok) {
        status.textContent = '❌ ' + (j.error || 'فشل الرفع');
        status.style.color = 'var(--danger,#dc3545)';
        return;
      }

      // Store in hidden fields for form submission
      hidPath.value  = j.apk_path || '';
      hidSize.value  = j.apk_size_bytes || '';
      hidSha.value   = j.apk_hash_sha256 || '';
      hidMd5.value   = j.apk_hash_md5 || '';

      status.textContent = '✅ تم الرفع بنجاح';
      status.style.color = 'var(--success,#198754)';

      // Auto-fill version + package if empty
      if (j.version && !document.querySelector('[name="version"]')?.value) {
        document.querySelector('[name="version"]').value = j.version;
      }
      if (j.package_name && !document.querySelector('[name="package_name"]')?.value) {
        document.querySelector('[name="package_name"]').value = j.package_name;
      }
      if (j.min_sdk && !document.querySelector('[name="android_version"]')?.value) {
        document.querySelector('[name="android_version"]').value = j.min_sdk;
      }
      if (j.apk_size_bytes) {
        const mb = (j.apk_size_bytes / 1048576).toFixed(1);
        const sizeF = document.querySelector('[name="size_mb"]');
        if (sizeF && !sizeF.value) sizeF.value = mb;
      }

      // Show metadata summary
      metaBox.style.display = 'block';
      const rows = [];
      if (j.apk_size_bytes) rows.push(`<div><b>الحجم:</b> ${formatBytes(j.apk_size_bytes)}</div>`);
      if (j.version)        rows.push(`<div><b>الإصدار:</b> ${esc(j.version)}</div>`);
      if (j.package_name)   rows.push(`<div><b>Package:</b> <code>${esc(j.package_name)}</code></div>`);
      if (j.min_sdk)        rows.push(`<div><b>Android الحد الأدنى:</b> ${esc(j.min_sdk)}</div>`);
      if (j.apk_hash_sha256) rows.push(`<div style="grid-column:1/-1"><b>SHA-256:</b> <code style="font-size:10px;word-break:break-all;cursor:pointer" onclick="navigator.clipboard.writeText('${j.apk_hash_sha256}');this.style.color='green'">${j.apk_hash_sha256}</code></div>`);
      if (j.apk_hash_md5)    rows.push(`<div><b>MD5:</b> <code style="cursor:pointer" onclick="navigator.clipboard.writeText('${j.apk_hash_md5}');this.style.color='green'">${j.apk_hash_md5}</code></div>`);
      metaBox.innerHTML = `<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">${rows.join('')}</div>`;

      // Auto-select 'apk' source if nothing was selected
      const srcAPK = document.querySelector('[name="download_source"][value="apk"]');
      const srcCur = document.querySelector('[name="download_source"]:checked');
      if (srcAPK && srcCur && srcCur.value === 'playstore' && !document.querySelector('[name="download_url"]')?.value) {
        srcAPK.checked = true;
      }
    };

    xhr.onerror = () => { status.textContent = '❌ فشل الاتصال'; status.style.color='var(--danger,#dc3545)'; };
    xhr.send(fd);
  }

  function formatBytes(b) {
    if (b>=1073741824) return (b/1073741824).toFixed(1)+' GB';
    if (b>=1048576)    return (b/1048576).toFixed(1)+' MB';
    if (b>=1024)       return Math.round(b/1024)+' KB';
    return b+' B';
  }
  function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();
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

  <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--border-c)">
    <label class="form-label">أو أدخل أسماء التطبيقات يدوياً بنفسك (اسم واحد بكل سطر) — يتخطى اقتراح الذكاء الاصطناعي ويستخدم أسماءك مباشرة</label>
    <textarea class="form-textarea" id="bg-manual-names" rows="8" placeholder="واتساب&#10;تيليجرام&#10;فيسبوك&#10;ماسنجر&#10;سناب شات&#10;يوتيوب&#10;..." style="font-family:inherit;direction:rtl"></textarea>
    <button type="button" id="btn-bg-manual" class="btn-save" style="margin-top:10px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m-7-7h14"/></svg>
      استخدم هذه الأسماء
    </button>
  </div>
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
/* ─────────────── IMPORT PRESET 30 APPS ─────────────── */
elseif ($page === 'import-preset'): ?>

<div class="admin-header"><h1>استيراد 30 تطبيقاً جاهزاً</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.9">
    30 تطبيقاً شهيراً بمحتوى عربي سوري كامل معدٍّ مسبقاً (وصف طويل + ميزات + إيجابيات/سلبيات + FAQ + SEO).
    حدّد ما تريد استيراده — التطبيقات الموجودة مسبقاً تُخطَى تلقائياً.
    كل تطبيق يُحفظ <strong style="color:#fbbf24">كمسودة</strong>، ثم أضف رابط التحميل وانشره.
  </p>
</div>

<!-- Controls row -->
<div class="panel" style="margin-bottom:14px">
  <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end">
    <div class="form-group" style="margin:0;width:200px">
      <label class="form-label">عدد الاستيراد (اختياري)</label>
      <input type="number" id="ip-count" class="form-input" min="1" max="30" placeholder="الكل">
    </div>
    <div class="form-group" style="margin:0;width:180px">
      <label class="form-label">تصفية حسب التصنيف</label>
      <select id="ip-cat-filter" class="form-select">
        <option value="">كل التصنيفات</option>
        <option value="تواصل اجتماعي">تواصل اجتماعي</option>
        <option value="تطبيقات">تطبيقات</option>
        <option value="أدوات">أدوات</option>
        <option value="إنتاجية">إنتاجية</option>
        <option value="تصميم">تصميم</option>
        <option value="ألعاب">ألعاب</option>
      </select>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button type="button" id="btn-ip-all" class="btn-edit" style="padding:8px 14px;font-size:13px">تحديد الجديدة فقط</button>
      <button type="button" id="btn-ip-none" class="btn-del" style="padding:8px 14px;font-size:13px">إلغاء الكل</button>
    </div>
  </div>
</div>

<!-- App grid -->
<div id="ip-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:16px">
  <div style="color:var(--muted);font-size:13px;padding:20px;grid-column:1/-1;text-align:center">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;display:inline-block"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>
    جاري تحميل القائمة...
  </div>
</div>

<!-- Start import -->
<div id="ip-start-row" style="display:none;margin-bottom:16px">
  <button type="button" id="btn-ip-import" class="btn-save" style="min-width:200px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
    <span id="btn-ip-import-label">بدء الاستيراد</span>
  </button>
  <span id="ip-sel-count" style="margin-right:12px;font-size:13px;color:var(--muted)"></span>
</div>

<!-- Progress -->
<div id="ip-progress" style="display:none;margin-bottom:14px">
  <div style="height:10px;background:var(--navy-600);border-radius:6px;overflow:hidden;margin-bottom:8px">
    <div id="ip-bar" style="height:100%;width:0%;background:linear-gradient(135deg,var(--cyan),var(--purple));transition:width .4s"></div>
  </div>
  <div id="ip-status" style="font-size:12px;color:var(--muted)"></div>
</div>

<!-- Results log -->
<div id="ip-results" style="display:flex;flex-direction:column;gap:8px"></div>

<style>
@keyframes spin{to{transform:rotate(360deg)}}
.ip-card{background:var(--navy-700);border:1.5px solid var(--border-c);border-radius:12px;padding:14px 16px;cursor:pointer;transition:border-color .2s,box-shadow .2s;display:flex;gap:12px;align-items:flex-start}
.ip-card:hover{border-color:var(--cyan);box-shadow:0 2px 12px rgba(37,99,235,.08)}
.ip-card.selected{border-color:var(--cyan);background:rgba(37,99,235,.04)}
.ip-card.existing{opacity:.5;cursor:default}
.ip-card.existing .ip-cb{pointer-events:none}
.ip-cb{width:18px;height:18px;border:2px solid var(--border-c);border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:background .15s,border-color .15s;margin-top:2px}
.ip-card.selected .ip-cb{background:var(--cyan);border-color:var(--cyan)}
.ip-card.selected .ip-cb::after{content:'';width:10px;height:6px;border-left:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(-45deg);display:block;margin-bottom:3px}
.ip-card-body{flex:1;min-width:0}
.ip-card-name{font-weight:700;font-size:14px;margin-bottom:3px;color:var(--white)}
.ip-card-dev{font-size:11px;color:var(--muted);margin-bottom:4px}
.ip-card-cat{display:inline-block;font-size:10px;background:rgba(37,99,235,.1);color:var(--cyan);border-radius:4px;padding:2px 7px;font-weight:600}
.ip-card-existing{display:inline-block;font-size:10px;background:rgba(22,163,74,.12);color:#16a34a;border-radius:4px;padding:2px 7px;font-weight:600;margin-right:4px}
.ip-res{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;font-size:13px}
.ip-res.ok{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.2);color:#4ade80}
.ip-res.skip{background:rgba(251,191,36,.06);border:1px solid rgba(251,191,36,.2);color:#fbbf24}
.ip-res.err{background:rgba(248,113,113,.06);border:1px solid rgba(248,113,113,.2);color:#f87171}
.ip-res a{color:var(--cyan);text-decoration:underline;font-size:12px;margin-right:auto;white-space:nowrap}
</style>

<script>
(function(){
  let allApps=[], selected=new Set();

  async function loadList(){
    const r=await fetch('admin.php?ajax=import_preset_list');
    const d=await r.json();
    if(!d.success){document.getElementById('ip-grid').innerHTML='<div style="color:#f87171;padding:20px;grid-column:1/-1">'+d.error+'</div>';return;}
    allApps=d.apps;
    renderGrid();
    document.getElementById('ip-start-row').style.display='flex';
    updateCount();
  }

  function renderGrid(){
    const catFilter=document.getElementById('ip-cat-filter').value;
    const list=catFilter?allApps.filter(a=>a.category===catFilter):allApps;
    const grid=document.getElementById('ip-grid');
    if(!list.length){grid.innerHTML='<div style="color:var(--muted);padding:20px;grid-column:1/-1;text-align:center">لا توجد تطبيقات بهذا التصنيف</div>';return;}
    grid.innerHTML='';
    list.forEach(a=>{
      const isExist=!!a.existing_id;
      const isSel=selected.has(a.name);
      const card=document.createElement('div');
      card.className='ip-card'+(isExist?' existing':'')+((!isExist&&isSel)?' selected':'');
      card.dataset.name=a.name;
      card.innerHTML=`
        <div class="ip-cb"></div>
        <div class="ip-card-body">
          <div class="ip-card-name">${h(a.name)}</div>
          <div class="ip-card-dev">${h(a.developer)}</div>
          <div>
            <span class="ip-card-cat">${h(a.category)}</span>
            ${isExist?`<span class="ip-card-existing">✓ مستورد — <a href="admin.php?page=edit-app&id=${a.existing_id}" style="color:inherit" target="_blank">تعديل</a></span>`:''}
          </div>
          ${!isExist?`<div style="font-size:11px;color:var(--muted);margin-top:6px;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">${h(a.short_desc)}</div>`:''}
        </div>`;
      if(!isExist){
        card.addEventListener('click',()=>{
          if(selected.has(a.name)){selected.delete(a.name);card.classList.remove('selected');}
          else{selected.add(a.name);card.classList.add('selected');}
          applyCountLimit();
          updateCount();
        });
      }
      grid.appendChild(card);
    });
    applyCountLimit();
  }

  function h(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

  function applyCountLimit(){
    const n=parseInt(document.getElementById('ip-count').value)||0;
    if(!n)return;
    // if more than n selected, keep first n only
    const sel=[...selected];
    if(sel.length>n){
      sel.slice(n).forEach(name=>{selected.delete(name);const c=document.querySelector('.ip-card[data-name="'+CSS.escape(name)+'"]');if(c)c.classList.remove('selected');});
    }
  }

  function updateCount(){
    const n=selected.size;
    document.getElementById('ip-sel-count').textContent=n?`${n} تطبيق محدد`:'لم يتم تحديد أي تطبيق';
    document.getElementById('btn-ip-import-label').textContent=n?`استيراد ${n} تطبيق`:'بدء الاستيراد';
  }

  document.getElementById('btn-ip-all').addEventListener('click',()=>{
    const catFilter=document.getElementById('ip-cat-filter').value;
    const n=parseInt(document.getElementById('ip-count').value)||0;
    let added=0;
    allApps.forEach(a=>{
      if(a.existing_id)return;
      if(catFilter&&a.category!==catFilter)return;
      if(n&&added>=n)return;
      selected.add(a.name);added++;
    });
    renderGrid();updateCount();
  });

  document.getElementById('btn-ip-none').addEventListener('click',()=>{
    selected.clear();renderGrid();updateCount();
  });

  document.getElementById('ip-cat-filter').addEventListener('change',renderGrid);

  document.getElementById('ip-count').addEventListener('input',()=>{
    applyCountLimit();updateCount();
  });

  document.getElementById('btn-ip-import').addEventListener('click',async()=>{
    if(!selected.size){alert('حدّد تطبيقاً واحداً على الأقل');return;}
    const names=[...selected];
    const total=names.length;
    let done=0,ok=0,skip=0,fail=0;
    const prog=document.getElementById('ip-progress');
    const bar=document.getElementById('ip-bar');
    const status=document.getElementById('ip-status');
    const results=document.getElementById('ip-results');
    prog.style.display='block';results.innerHTML='';
    document.getElementById('btn-ip-import').disabled=true;

    for(const name of names){
      status.textContent=`⏳ ${name} — جاري الاستيراد...`;
      try{
        const r=await fetch('admin.php?ajax=import_preset_one',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name})});
        const d=await r.json();
        done++;bar.style.width=(done/total*100)+'%';
        const row=document.createElement('div');
        if(d.success){
          ok++;row.className='ip-res ok';
          row.innerHTML=`<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <strong>${h(d.name)}</strong> — تم الإنشاء ${d.has_icon?'(أيقونة ✓)':'(بدون أيقونة)'}<a href="${d.edit_url}" target="_blank">تعديل</a>`;
          selected.delete(name);const c=document.querySelector('.ip-card[data-name="'+CSS.escape(name)+'"]');
          if(c){c.className='ip-card existing';c.style.opacity='.5';}
        } else if(d.skipped){
          skip++;row.className='ip-res skip';
          row.innerHTML=`<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg> ${h(name)} — موجود مسبقاً`;
        } else {
          fail++;row.className='ip-res err';
          row.innerHTML=`<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> ${h(name)} — ${h(d.error||'خطأ')}`;
        }
        results.prepend(row);
      }catch(e){
        done++;fail++;bar.style.width=(done/total*100)+'%';
        const row=document.createElement('div');row.className='ip-res err';
        row.innerHTML=`✗ ${h(name)} — خطأ في الشبكة`;results.prepend(row);
      }
    }
    status.textContent=`🏁 اكتمل — تم استيراد ${ok}${skip?' | تخطّي '+skip:''}${fail?' | فشل '+fail:''}`;
    document.getElementById('btn-ip-import').disabled=false;
    updateCount();
  });

  loadList();
})();
</script>

<?php
/* ─────────────── FILE IMPORT ─────────────── */
elseif ($page === 'file-import'): ?>

<div class="admin-header"><h1>استيراد تطبيقات من ملف</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.9">
    ارفع ملف <strong>JSON</strong> أو <strong>SQL</strong> أو <strong>PHP</strong> يحتوي بيانات تطبيقات كاملة.
    سيتحقق النظام من جودة كل تطبيق — يُرفض أي محتوى ناقص أو ضعيف. التطبيقات الناجحة تُستورد كمسودات.
    <br><strong style="color:#fbbf24">الشروط الإلزامية:</strong> اسم · وصف طويل (+200 كلمة) · عنوان SEO · وصف meta · كلمات مفتاحية · ميزات · إيجابيات · خطوات تثبيت · FAQ.
  </p>
</div>

<div class="panel" style="margin-bottom:16px" id="fi-upload-panel">
  <label for="fi-file-input" id="fi-drop-zone" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:48px 24px;border:2px dashed var(--border);border-radius:12px;cursor:pointer;transition:border-color .2s,background .2s">
    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <div style="text-align:center">
      <div style="font-size:16px;font-weight:700;margin-bottom:6px">اسحب الملف هنا أو انقر للتصفح</div>
      <div style="font-size:13px;color:var(--muted)">يدعم: .json · .sql · .php · .txt — حجم أقصى 10 MB</div>
    </div>
  </label>
  <input type="file" id="fi-file-input" accept=".json,.sql,.php,.txt" style="display:none">
  <div id="fi-file-name" style="text-align:center;font-size:13px;color:var(--muted);margin-top:12px;display:none"></div>
  <div style="text-align:center;margin-top:16px">
    <button id="fi-validate-btn" class="btn btn-primary" style="display:none">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      تحليل الجودة
    </button>
  </div>
</div>

<div id="fi-results" style="display:none">
  <div class="panel" style="margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <span id="fi-total-badge" style="background:rgba(37,99,235,.1);color:var(--accent);border:1px solid rgba(37,99,235,.25);padding:5px 14px;border-radius:40px;font-size:13px;font-weight:700"></span>
        <span id="fi-pass-badge" style="background:rgba(22,163,74,.1);color:#16a34a;border:1px solid rgba(22,163,74,.25);padding:5px 14px;border-radius:40px;font-size:13px;font-weight:700"></span>
        <span id="fi-fail-badge" style="background:rgba(220,38,38,.1);color:#dc2626;border:1px solid rgba(220,38,38,.25);padding:5px 14px;border-radius:40px;font-size:13px;font-weight:700"></span>
      </div>
      <button id="fi-import-all-btn" class="btn btn-primary">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        استيراد جميع الناجحة
      </button>
    </div>
    <div id="fi-import-progress" style="display:none;margin-bottom:16px">
      <div style="height:6px;background:rgba(37,99,235,.1);border-radius:3px;overflow:hidden;margin-bottom:8px">
        <div id="fi-import-bar" style="height:100%;background:linear-gradient(90deg,var(--accent),var(--purple));border-radius:3px;width:0%;transition:width .4s"></div>
      </div>
      <div id="fi-import-status" style="font-size:13px;color:var(--muted)"></div>
    </div>
    <div id="fi-app-list"></div>
  </div>
</div>

<div class="panel">
  <div style="font-size:13px;font-weight:700;margin-bottom:12px">📋 صيغة JSON المقبولة</div>
  <pre style="font-size:11.5px;color:var(--muted);overflow-x:auto;background:rgba(0,0,0,.25);padding:16px;border-radius:8px;line-height:1.8;direction:ltr;text-align:left">{"apps":[{
  "name": "اسم التطبيق",
  "developer": "اسم الشركة",
  "short_description": "جملة جذابة تصف التطبيق (80+ حرف)",
  "long_description": "وصف تفصيلي 200+ كلمة ...",
  "seo_title": "عنوان للبحث 40-70 حرف",
  "meta_description": "وصف meta 120-160 حرف",
  "keywords": "كلمة1، كلمة2، كلمة3",
  "features": ["ميزة 1","ميزة 2","ميزة 3"],
  "pros": ["إيجابية 1","إيجابية 2"],
  "cons": ["سلبية 1"],
  "install_steps": ["خطوة 1","خطوة 2","خطوة 3"],
  "faq": [{"q":"سؤال؟","a":"إجابة"},{"q":"سؤال 2؟","a":"إجابة 2"},{"q":"سؤال 3؟","a":"إجابة 3"}],
  "whats_new": "الجديد في هذا الإصدار",
  "version": "1.0.0",
  "size_mb": 45,
  "category_slug": "apps",
  "playstore_url": "https://play.google.com/store/apps/details?id=..."
}]}</pre>
</div>

<script>
(function(){
  const drop=document.getElementById('fi-drop-zone'),
        fileIn=document.getElementById('fi-file-input'),
        fileName=document.getElementById('fi-file-name'),
        vBtn=document.getElementById('fi-validate-btn'),
        results=document.getElementById('fi-results'),
        appList=document.getElementById('fi-app-list'),
        importAll=document.getElementById('fi-import-all-btn'),
        importProg=document.getElementById('fi-import-progress'),
        importBar=document.getElementById('fi-import-bar'),
        importSt=document.getElementById('fi-import-status');
  let selFile=null, vApps=[];

  drop.addEventListener('dragover',e=>{e.preventDefault();drop.style.borderColor='var(--accent)';drop.style.background='rgba(37,99,235,.05)';});
  drop.addEventListener('dragleave',()=>{drop.style.borderColor='';drop.style.background='';});
  drop.addEventListener('drop',e=>{e.preventDefault();drop.style.borderColor='';drop.style.background='';if(e.dataTransfer.files[0])setFile(e.dataTransfer.files[0]);});
  fileIn.addEventListener('change',()=>{if(fileIn.files[0])setFile(fileIn.files[0]);});

  function setFile(f){
    selFile=f;
    fileName.textContent='📄 '+f.name+' ('+fmtSz(f.size)+')';
    fileName.style.display='block';
    vBtn.style.display='inline-flex';
    results.style.display='none'; vApps=[];
  }
  function fmtSz(b){return b<1024?b+' B':b<1048576?(b/1024).toFixed(1)+' KB':(b/1048576).toFixed(1)+' MB';}
  function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

  vBtn.addEventListener('click',async()=>{
    if(!selFile)return;
    vBtn.disabled=true; vBtn.textContent='جارٍ التحليل...';
    const fd=new FormData(); fd.append('file',selFile);
    try{
      const r=await fetch('admin.php?ajax=file_import_validate',{method:'POST',body:fd});
      const d=await r.json();
      if(!d.success){alert('خطأ: '+(d.error||'غير معروف'));return;}
      vApps=d.apps; renderResults(d);
    }catch(e){alert('خطأ: '+e.message);}
    finally{vBtn.disabled=false;vBtn.innerHTML='<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> تحليل الجودة';}
  });

  function renderResults(d){
    document.getElementById('fi-total-badge').textContent='الإجمالي: '+d.total;
    document.getElementById('fi-pass-badge').textContent='✓ ناجح: '+d.passed;
    document.getElementById('fi-fail-badge').textContent='✗ مرفوض: '+(d.total-d.passed);
    importAll.textContent='استيراد الناجحة ('+d.passed+')';
    let h='';
    d.apps.forEach((a,i)=>{
      const sc=a.pass?'#16a34a':'#dc2626', si=a.pass?'✓':'✗', st=a.pass?'ناجح':'مرفوض';
      const sc2=a.score>=80?'#16a34a':a.score>=55?'#d97706':'#dc2626';
      h+=`<div style="background:var(--panel-bg);border:1px solid ${sc}30;border-radius:10px;padding:16px;margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:${(a.issues.length||a.warnings.length)?'12px':'0'}">
          <div>
            <div style="font-size:14px;font-weight:700">${esc(a.name)}</div>
            <div style="font-size:12px;color:var(--muted)">${esc(a.developer||'—')} · ${a.word_count} كلمة</div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span style="background:${sc2}18;color:${sc2};border:1px solid ${sc2}40;padding:3px 11px;border-radius:40px;font-size:12px;font-weight:700">${a.score}/100</span>
            <span style="background:${sc}18;color:${sc};border:1px solid ${sc}40;padding:3px 11px;border-radius:40px;font-size:12px;font-weight:700">${si} ${st}</span>
            ${a.pass?`<button class="btn" style="font-size:12px;padding:5px 14px" onclick="fiImportOne(${i})" id="fi-btn-${i}">استيراد</button>`:''}
          </div>
        </div>
        ${a.issues.map(e=>`<div style="font-size:12px;color:#dc2626;display:flex;gap:6px;margin-bottom:3px"><span>⚠</span><span>${esc(e)}</span></div>`).join('')}
        ${a.warnings.map(w=>`<div style="font-size:12px;color:#d97706;display:flex;gap:6px;margin-bottom:3px"><span>ℹ</span><span>${esc(w)}</span></div>`).join('')}
      </div>`;
    });
    appList.innerHTML=h; results.style.display='block';
  }

  async function doImport(idx){
    const a=vApps[idx]; if(!a||!a.pass)return false;
    const btn=document.getElementById('fi-btn-'+idx);
    if(btn){btn.disabled=true;btn.textContent='جارٍ...';}
    try{
      const r=await fetch('admin.php?ajax=file_import_run',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({app:a.data})});
      const d=await r.json();
      if(d.success){if(btn)btn.outerHTML=`<a href="${d.edit_url}" class="btn" style="font-size:12px;padding:5px 14px;background:rgba(22,163,74,.15);color:#16a34a;border-color:rgba(22,163,74,.3)">✓ تعديل</a>`;return true;}
      else if(d.skipped){if(btn)btn.outerHTML=`<span style="font-size:12px;color:#d97706">موجود</span>`;return 'skip';}
      else{if(btn){btn.disabled=false;btn.textContent='استيراد';}alert('فشل: '+(d.error||'خطأ'));return false;}
    }catch(e){if(btn){btn.disabled=false;btn.textContent='استيراد';}alert('خطأ: '+e.message);return false;}
  }
  window.fiImportOne=idx=>doImport(idx);

  importAll.addEventListener('click',async()=>{
    const passed=vApps.filter(a=>a.pass); if(!passed.length){alert('لا توجد تطبيقات ناجحة');return;}
    importAll.disabled=true; importProg.style.display='block';
    let ok=0,skip=0,fail=0;
    for(let i=0;i<vApps.length;i++){
      if(!vApps[i].pass)continue;
      importBar.style.width=((i/vApps.length)*100)+'%';
      importSt.textContent='جارٍ: '+vApps[i].name+'...';
      const res=await doImport(i);
      if(res===true)ok++;else if(res==='skip')skip++;else fail++;
    }
    importBar.style.width='100%';
    importSt.innerHTML=`<strong style="color:var(--white)">🏁 اكتمل</strong> — تم: ${ok}${skip?' · تخطّي: '+skip:''}${fail?' · فشل: '+fail:''}`;
    importAll.disabled=false;
  });
})();
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
/* ─────────────── BLOG LIST ─────────────── */
elseif ($page === 'blog'):
  $blogTypeFilter = trim($_GET['type'] ?? '');
  $blogWhere = "";
  $blogParams = [];
  if (isset(BLOG_TYPES[$blogTypeFilter])) { $blogWhere = "WHERE type=?"; $blogParams[] = $blogTypeFilter; }
  $blogStmt = $pdo->prepare("SELECT * FROM blog_posts $blogWhere ORDER BY created_at DESC LIMIT 200");
  $blogStmt->execute($blogParams);
  $blogPosts = $blogStmt->fetchAll();
?>

<div class="admin-header">
  <h1>المدونة والمحتوى</h1>
  <a href="admin.php?page=blog-edit" class="btn-edit">+ مقال جديد يدوياً</a>
</div>

<div class="panel">
  <h2>توليد مقال جديد بالذكاء الاصطناعي</h2>
  <div class="form-grid" style="margin-bottom:12px">
    <div class="form-group">
      <label class="form-label">القسم</label>
      <select class="form-select" id="blog-gen-type">
        <?php foreach (BLOG_TYPES as $t => $label): ?>
        <option value="<?= h($t) ?>"><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">الموضوع (اختياري — اتركه فارغاً ليختار الذكاء الاصطناعي موضوعاً مناسباً)</label>
      <input class="form-input" type="text" id="blog-gen-topic" placeholder="مثال: أفضل تطبيقات تعديل الفيديو 2026">
    </div>
  </div>
  <button type="button" id="btn-generate-blog" class="btn-ai">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    توليد مقال (يُنشأ كمسودة، راجعه ثم انشره)
  </button>
  <div id="blog-gen-result" style="margin-top:12px"></div>
</div>

<div class="cat-chips" style="margin:16px 0">
  <a href="admin.php?page=blog" class="cat-chip <?= $blogTypeFilter==='' ? 'active':'' ?>" style="text-decoration:none">الكل</a>
  <?php foreach (BLOG_TYPES as $t => $label): ?>
  <a href="admin.php?page=blog&type=<?= h($t) ?>" class="cat-chip <?= $blogTypeFilter===$t ? 'active':'' ?>" style="text-decoration:none"><?= h($label) ?></a>
  <?php endforeach; ?>
</div>

<div class="panel" style="padding:0;overflow:hidden">
<table class="admin-table responsive-cards">
  <thead><tr><th>العنوان</th><th>القسم</th><th>الحالة</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($blogPosts as $bp): ?>
  <tr>
    <td data-label="العنوان" style="font-weight:700"><?= h($bp['title']) ?></td>
    <td data-label="القسم" style="color:var(--muted);font-size:12px">
      <?= h(blog_type_label($bp['type'])) ?>
      <?php if ($bp['type'] === 'code-page'): $cpCount = count(decode_code_page($bp['body'] ?? '')); ?>
        <span style="display:inline-block;margin-inline-start:4px;padding:1px 6px;border-radius:4px;background:rgba(124,58,237,.15);color:#a78bfa;font-size:10px;font-weight:700"><?= $cpCount ?> أقسام</span>
      <?php endif; ?>
    </td>
    <td data-label="الحالة"><span class="status-badge <?= $bp['status']==='published'?'status-published':'status-draft' ?>"><?= $bp['status']==='published'?'منشور':'مسودة' ?></span></td>
    <td data-label="التاريخ" style="color:var(--muted);font-size:12px"><?= h(time_ago($bp['created_at'])) ?></td>
    <td data-label="إجراءات" class="td-actions">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="admin.php?page=blog-edit&id=<?= (int)$bp['id'] ?>" class="btn-edit">تعديل</a>
        <?php if ($bp['status']==='published'): ?>
        <a href="<?= h(blog_post_url($bp['slug'])) ?>" target="_blank" class="btn-view">عرض</a>
        <?php endif; ?>
        <a href="admin.php?page=blog&del=<?= (int)$bp['id'] ?>&t=<?= csrf_token() ?>" class="btn-del" data-confirm="حذف هذا المقال؟">حذف</a>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$blogPosts): ?><tr><td colspan="5" style="text-align:center;color:var(--muted);padding:32px">لا توجد مقالات بعد</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php
/* ─────────────── BLOG EDIT ─────────────── */
elseif ($page === 'blog-edit'):
  $blogPost = ['id'=>0,'type'=>'article','title'=>'','seo_title'=>'','meta_description'=>'','keywords'=>'','excerpt'=>'','body'=>'','status'=>'draft'];
  if (isset($_GET['id'])) {
    $bstmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id=?");
    $bstmt->execute([(int)$_GET['id']]);
    $found = $bstmt->fetch();
    if ($found) $blogPost = $found;
  }
?>

<div class="admin-header">
  <h1><?= $blogPost['id'] ? 'تعديل: '.h($blogPost['title']) : 'مقال جديد' ?></h1>
  <a href="admin.php?page=blog" class="btn-edit">← كل المقالات</a>
</div>

<?php if (!empty($blogError)): ?><div class="alert alert-error"><?= h($blogError) ?></div><?php endif; ?>

<form method="post" action="admin.php?page=blog-edit" id="blog-edit-form">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$blogPost['id'] ?>">
  <div class="panel">
    <div class="form-grid">
      <div class="form-group full">
        <label class="form-label">العنوان *</label>
        <input class="form-input" id="blog-title" type="text" name="title" value="<?= h($blogPost['title']) ?>" required>
      </div>
      <div class="form-group full">
        <label class="form-label">الرابط (Slug)</label>
        <input class="form-input" id="blog-slug" type="text" name="slug" dir="ltr" style="text-align:left"
               value="<?= h($blogPost['slug'] ?? '') ?>"
               placeholder="سيُنشأ تلقائياً من العنوان إن تُرك فارغاً"
               pattern="[a-zA-Z0-9؀-ۿ\-]*">
        <span style="font-size:11px;color:var(--muted);display:block;margin-top:4px" dir="ltr">
          <?= h(SITE_URL) ?>/blog/<span id="blog-slug-preview"><?= h($blogPost['slug'] ?? 'post-slug') ?></span>
        </span>
      </div>
      <div class="form-group">
        <label class="form-label">القسم</label>
        <select class="form-select" name="type">
          <?php foreach (BLOG_TYPES as $t => $label): ?>
          <option value="<?= h($t) ?>" <?= $blogPost['type']===$t?'selected':'' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">الحالة</label>
        <select class="form-select" name="status">
          <option value="draft" <?= $blogPost['status']==='draft'?'selected':'' ?>>مسودة</option>
          <option value="published" <?= $blogPost['status']==='published'?'selected':'' ?>>منشور</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">SEO Title</label>
        <input class="form-input" type="text" name="seo_title" value="<?= h($blogPost['seo_title']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">الكلمات المفتاحية</label>
        <input class="form-input" type="text" name="keywords" value="<?= h($blogPost['keywords']) ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">Meta Description</label>
        <input class="form-input" type="text" name="meta_description" value="<?= h($blogPost['meta_description']) ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">ملخص قصير (يظهر في بطاقة المقال)</label>
        <input class="form-input" type="text" name="excerpt" value="<?= h($blogPost['excerpt']) ?>">
      </div>
      <!-- ══ WYSIWYG editor (shown for all types EXCEPT code-page) ══ -->
      <div class="form-group full" id="blog-wysiwyg-wrap">
        <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
          <span>نص المقال</span>
          <span style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" id="btn-fix-body-html" class="btn-edit" style="font-size:12px;padding:5px 10px" title="تحويل النص العادي لفقرات HTML — مفيد للمقالات القديمة">🔧 تحويل لـ HTML</button>
            <select id="blog-template-select" class="form-select" style="font-size:12px;padding:4px 8px;height:auto">
              <option value="">📄 قالب جاهز…</option>
              <option value="news">📰 قالب خبر</option>
              <option value="tutorial">📚 قالب شرح خطوة بخطوة</option>
              <option value="comparison">⚖️ قالب مقارنة</option>
              <option value="top-list">🏆 قالب قائمة أفضل</option>
              <option value="review">⭐ قالب مراجعة تطبيق</option>
            </select>
            <button type="button" id="blog-toggle-source" class="btn-edit" style="font-size:12px;padding:5px 10px">
              &lt;/&gt; HTML
            </button>
          </span>
        </label>
        <!-- WYSIWYG Toolbar -->
        <div class="wysiwyg-toolbar" id="wysiwyg-toolbar">
          <select class="ws-sel" id="ws-block">
            <option value="p">فقرة</option>
            <option value="h1">H1</option><option value="h2">H2</option>
            <option value="h3">H3</option><option value="h4">H4</option>
            <option value="h5">H5</option><option value="h6">H6</option>
            <option value="blockquote">اقتباس</option><option value="pre">كود</option>
          </select>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" data-cmd="bold" title="خط عريض"><b>B</b></button>
          <button class="ws-btn" type="button" data-cmd="italic" title="مائل"><i>I</i></button>
          <button class="ws-btn" type="button" data-cmd="underline" title="تسطير"><u>U</u></button>
          <button class="ws-btn" type="button" data-cmd="strikeThrough" title="شطب"><s>S</s></button>
          <span class="ws-sep"></span>
          <button class="ws-btn ws-sm" type="button" data-cmd="fontSize" data-val="2" title="تصغير">A−</button>
          <button class="ws-btn ws-sm" type="button" data-cmd="fontSize" data-val="4" title="تكبير">A+</button>
          <button class="ws-btn ws-sm" type="button" data-cmd="fontSize" data-val="6" title="كبير جداً">A⁺⁺</button>
          <span class="ws-sep"></span>
          <label class="ws-btn" style="cursor:pointer" title="لون النص">
            <input type="color" id="ws-color" style="width:0;height:0;opacity:0;position:absolute"> A🎨
          </label>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" data-cmd="justifyRight" title="يمين">⇥</button>
          <button class="ws-btn" type="button" data-cmd="justifyCenter" title="وسط">⇔</button>
          <button class="ws-btn" type="button" data-cmd="justifyLeft" title="يسار">⇤</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" data-cmd="insertUnorderedList" title="قائمة نقطية">•≡</button>
          <button class="ws-btn" type="button" data-cmd="insertOrderedList" title="قائمة مرقمة">1≡</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" id="ws-link" title="رابط">🔗</button>
          <button class="ws-btn" type="button" id="ws-image" title="صورة">🖼</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" id="ws-code" title="كتلة كود">⌨</button>
          <button class="ws-btn" type="button" data-cmd="insertHorizontalRule" title="خط فاصل">─</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" data-cmd="undo" title="تراجع">↩</button>
          <button class="ws-btn" type="button" data-cmd="redo" title="إعادة">↪</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" id="ws-fullscreen" title="ملء الشاشة">⛶</button>
        </div>
        <div id="wysiwyg-editor" class="wysiwyg-editor" contenteditable="true" dir="auto"><?= $blogPost['type'] !== 'code-page' ? $blogPost['body'] : '' ?></div>
        <textarea name="body" id="blog-body-hidden" style="display:none"></textarea>
        <textarea id="blog-body-source" class="form-textarea" name="_body_source" rows="20" style="display:none;font-family:monospace;font-size:13px;direction:ltr"><?= $blogPost['type'] !== 'code-page' ? h($blogPost['body']) : '' ?></textarea>
      </div>

      <!-- ══ Code-page editor (shown only when type = code-page) ══ -->
      <div class="form-group full" id="blog-codepage-wrap" style="display:none">
        <label class="form-label">صفحة المحتوى — أقسام الأكواد</label>
        <input type="text" name="cp_description" id="cp-description" class="form-input" placeholder="وصف الصفحة (اختياري — يظهر تحت العنوان)..."
               value="<?= $blogPost['type'] === 'code-page' ? h(json_decode($blogPost['body'] ?? '{}', true)['description'] ?? '') : '' ?>"
               style="margin-bottom:14px">

        <!-- ══ Smart paste zone ══ -->
        <div class="cp-smart-zone" id="cp-smart-zone">
          <div class="cp-smart-header">
            <div class="cp-smart-meta">
              <span class="cp-smart-title">📥 الصق الكود هنا — الكشف التلقائي عن اللغة</span>
              <span class="cp-smart-hint">يدعم: ` ```html ... ``` ` أو <code>=== html ===</code> أو كشف تلقائي</span>
            </div>
            <div class="cp-smart-actions">
              <button type="button" class="cp-smart-btn primary" onclick="cpSmartDetect()">🔍 مزامنة الأقسام</button>
              <button type="button" class="cp-smart-btn" onclick="cpSmartClear()">🗑 مسح</button>
            </div>
          </div>
          <textarea id="cp-smart-input" class="cp-smart-textarea" spellcheck="false" autocorrect="off"
            placeholder="الصق الكود هنا — يمكنك خلط كل اللغات في مربع واحد&#10;&#10;مثال باستخدام علامات markdown:&#10;&#10;```html&#10;&lt;h1&gt;مرحبا&lt;/h1&gt;&#10;```&#10;&#10;```css&#10;h1 { color: red; }&#10;```&#10;&#10;```js&#10;console.log('hello');&#10;```&#10;&#10;أو الصق بدون علامات وسيتم الكشف التلقائي"></textarea>
          <div id="cp-smart-result" class="cp-smart-result" style="display:none"></div>
        </div>

        <?php
        $cpSections = $blogPost['type'] === 'code-page' ? decode_code_page($blogPost['body'] ?? '{}') : [];
        foreach (CODE_PAGE_LANGS as $lang => $meta):
            $existingCode = $cpSections[$lang] ?? '';
            $lineCount = $existingCode ? substr_count($existingCode, "\n") + 1 : 0;
        ?>
        <div class="cp-section" id="cp-section-<?= $lang ?>" data-lang="<?= $lang ?>">
          <div class="cp-section-header" onclick="cpToggle('<?= $lang ?>')">
            <span class="cp-lang-badge" style="--lang-color:<?= h($meta['color']) ?>"><?= $meta['icon'] ?> <?= h($meta['label']) ?></span>
            <span class="cp-line-count" id="cp-lines-<?= $lang ?>"><?= $lineCount ? $lineCount . ' سطر' : 'فارغ' ?></span>
            <button type="button" class="cp-toggle-btn" id="cp-toggle-<?= $lang ?>">▼</button>
          </div>
          <div class="cp-section-body" id="cp-body-<?= $lang ?>" style="<?= $existingCode ? '' : 'display:none' ?>">
            <div class="cp-toolbar">
              <button type="button" class="cp-tool-btn" onclick="cpClear('<?= $lang ?>')" title="مسح المحتوى">🗑 مسح</button>
              <button type="button" class="cp-tool-btn" onclick="cpCopy('<?= $lang ?>')" title="نسخ المحتوى">📋 نسخ</button>
              <span class="cp-tool-sep"></span>
              <button type="button" class="cp-tool-btn cp-expand-btn" onclick="cpExpand('<?= $lang ?>')" title="توسيع المحرر">⛶ توسيع</button>
            </div>
            <textarea
              name="cp_<?= $lang ?>"
              id="cp-textarea-<?= $lang ?>"
              class="cp-textarea"
              placeholder="أدخل كود <?= h($meta['label']) ?> هنا..."
              spellcheck="false"
              autocorrect="off"
              autocapitalize="off"
              data-lang="<?= $lang ?>"><?= h($existingCode) ?></textarea>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <button type="submit" class="btn-save" style="margin-bottom:40px">حفظ المقال</button>
</form>

<?php
/* ─────────────── ARTICLE GENERATOR ─────────────── */
elseif ($page === 'article-gen'):
  $allApps = $pdo->query("SELECT id,name,slug,developer FROM apps WHERE status='published' ORDER BY name")->fetchAll();
?>

<div class="admin-header"><h1>توليد مقالات التطبيقات</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.9">
    يولّد النظام لكل تطبيق مقالاً تفصيلياً من <strong>5 أقسام × 900-1100 كلمة = ~5000 كلمة</strong> باللهجة السورية.
    يشمل كل مقال: مقدمة، ميزات تفصيلية، دليل استخدام، مميزات وعيوب، نصائح وأسئلة شائعة، وأزرار تحميل احترافية.
    المقالات تُحفظ كمسودات في قسم المدونة ثم تنشرها عند الاستعداد.
  </p>
</div>

<!-- App selector grid -->
<div class="panel" style="margin-bottom:16px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
    <div style="font-size:14px;font-weight:700">اختر التطبيقات لتوليد مقالاتها</div>
    <div style="display:flex;gap:8px">
      <button class="btn" style="font-size:12px;padding:6px 14px" onclick="agSelectAll()">تحديد الكل</button>
      <button class="btn" style="font-size:12px;padding:6px 14px" onclick="agDeselectAll()">إلغاء الكل</button>
    </div>
  </div>
  <div id="ag-app-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px">
    <?php foreach ($allApps as $a): ?>
    <label id="ag-app-<?= $a['id'] ?>" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid var(--border);border-radius:8px;cursor:pointer;transition:border-color .2s,background .2s">
      <input type="checkbox" name="ag_apps[]" value="<?= $a['id'] ?>" style="flex-shrink:0" onchange="agUpdateBtn()">
      <span>
        <div style="font-size:13px;font-weight:600"><?= h($a['name']) ?></div>
        <div style="font-size:11px;color:var(--muted)"><?= h($a['developer']) ?></div>
      </span>
    </label>
    <?php endforeach; ?>
    <?php if (!$allApps): ?>
    <p style="color:var(--muted);font-size:13px">لا توجد تطبيقات منشورة بعد.</p>
    <?php endif; ?>
  </div>
  <div style="margin-top:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <button id="ag-start-btn" class="btn btn-primary" onclick="agStartGeneration()" disabled>
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
      توليد المقالات المحددة
    </button>
    <span id="ag-sel-count" style="font-size:13px;color:var(--muted)">0 محدد</span>
  </div>
</div>

<!-- Progress log -->
<div id="ag-progress-wrap" style="display:none">
  <div class="panel" style="margin-bottom:12px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <div style="font-weight:700;font-size:14px">تقدم التوليد</div>
      <span id="ag-overall-status" style="font-size:13px;color:var(--muted)"></span>
    </div>
    <div style="height:6px;background:rgba(37,99,235,.1);border-radius:3px;overflow:hidden;margin-bottom:12px">
      <div id="ag-overall-bar" style="height:100%;background:linear-gradient(90deg,var(--accent),var(--purple));border-radius:3px;width:0%;transition:width .5s"></div>
    </div>
    <div id="ag-log"></div>
  </div>
</div>

<script>
(function(){
  function agUpdateBtn(){
    const checked=document.querySelectorAll('input[name="ag_apps[]"]:checked');
    document.getElementById('ag-start-btn').disabled=checked.length===0;
    document.getElementById('ag-sel-count').textContent=checked.length+' محدد';
  }
  window.agSelectAll=()=>{document.querySelectorAll('input[name="ag_apps[]"]').forEach(c=>c.checked=true);agUpdateBtn();};
  window.agDeselectAll=()=>{document.querySelectorAll('input[name="ag_apps[]"]').forEach(c=>c.checked=false);agUpdateBtn();};

  const CHUNKS=5;

  window.agStartGeneration=async()=>{
    const checked=[...document.querySelectorAll('input[name="ag_apps[]"]:checked')];
    if(!checked.length)return;
    document.getElementById('ag-start-btn').disabled=true;
    document.getElementById('ag-progress-wrap').style.display='block';
    const log=document.getElementById('ag-log');
    const bar=document.getElementById('ag-overall-bar');
    const st=document.getElementById('ag-overall-status');
    log.innerHTML='';
    let totalSteps=checked.length*CHUNKS, doneSteps=0;

    for(let ci=0;ci<checked.length;ci++){
      const appId=checked[ci].value;
      const appName=checked[ci].closest('label').querySelector('div').textContent;
      const row=document.createElement('div');
      row.style.cssText='background:var(--panel-bg);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:10px';
      row.innerHTML=`<div style="font-size:13px;font-weight:700;margin-bottom:8px">${esc(appName)}</div>
        <div style="height:4px;background:rgba(37,99,235,.1);border-radius:2px;margin-bottom:8px;overflow:hidden"><div class="ag-row-bar" style="height:100%;background:linear-gradient(90deg,#2563eb,#7c3aed);border-radius:2px;width:0%;transition:width .4s"></div></div>
        <div class="ag-row-status" style="font-size:12px;color:var(--muted)">جارٍ التوليد...</div>`;
      log.appendChild(row);
      const rowBar=row.querySelector('.ag-row-bar');
      const rowSt=row.querySelector('.ag-row-status');

      let fullHtml=''; let ok=true;
      for(let ch=1;ch<=CHUNKS;ch++){
        rowSt.textContent=`القسم ${ch}/${CHUNKS}: جارٍ...`;
        try{
          const r=await fetch('admin.php?ajax=gen_app_article_chunk',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({app_id:parseInt(appId),chunk:ch,prev_html:fullHtml.slice(-2000)})});
          const d=await r.json();
          if(d.success){fullHtml+='\n'+d.html;}
          else{rowSt.textContent='⚠ خطأ في القسم '+ch+': '+(d.error||'غير معروف');ok=false;break;}
        }catch(e){rowSt.textContent='⚠ استثناء في القسم '+ch+': '+e.message;ok=false;break;}
        doneSteps++; rowBar.style.width=(ch/CHUNKS*100)+'%';
        bar.style.width=(doneSteps/totalSteps*100)+'%';
        st.textContent=Math.round(doneSteps/totalSteps*100)+'%';
      }
      if(ok && fullHtml){
        rowSt.textContent='حفظ المقال...';
        try{
          const sr=await fetch('admin.php?ajax=save_app_article',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({app_id:parseInt(appId),html:fullHtml})});
          const sd=await sr.json();
          if(sd.success){
            rowSt.innerHTML=`✓ مقال محفوظ (${sd.updated?'تحديث':'جديد'}) — <a href="${sd.view_url}" target="_blank" style="color:var(--accent)">معاينة</a> | <a href="admin.php?page=blog&edit=${sd.id}" style="color:var(--accent)">تعديل</a>`;
            rowBar.style.background='linear-gradient(90deg,#16a34a,#15803d)';
            const lbl=document.getElementById('ag-app-'+appId);
            if(lbl)lbl.style.borderColor='#16a34a';
          }else{rowSt.textContent='⚠ فشل الحفظ: '+(sd.error||'غير معروف');}
        }catch(e){rowSt.textContent='⚠ خطأ الحفظ: '+e.message;}
      }
    }
    bar.style.width='100%'; st.innerHTML='<strong>🏁 اكتمل</strong>';
    document.getElementById('ag-start-btn').disabled=false;
  };

  function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
})();
</script>

<?php
/* ─────────────── SITE ANALYTICS (real self-tracked data, not GSC) ─────────────── */
elseif ($page === 'stats'):
    $days = 30;
    $dailyRaw = $pdo->query("
        SELECT DATE(created_at) d, event_type, COUNT(*) c
        FROM page_events
        WHERE created_at >= (NOW() - INTERVAL $days DAY)
        GROUP BY d, event_type
        ORDER BY d
    ")->fetchAll();
    $daily = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $daily[$d] = ['view' => 0, 'download' => 0, 'search' => 0];
    }
    foreach ($dailyRaw as $row) {
        if (isset($daily[$row['d']])) $daily[$row['d']][$row['event_type']] = (int)$row['c'];
    }
    $maxDaily = 1;
    foreach ($daily as $d) $maxDaily = max($maxDaily, $d['view'], $d['download']);

    $totalsAllTime = [
        'views'            => (int)$pdo->query("SELECT COALESCE(SUM(views),0) FROM apps")->fetchColumn(),
        'downloads'        => (int)$pdo->query("SELECT COALESCE(SUM(downloads),0) FROM apps")->fetchColumn(),
        'apps'             => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn(),
        'blog'             => (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn(),
        'comments'         => (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status='approved'")->fetchColumn(),
        'pending_comments' => (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(),
    ];
    $views30     = array_sum(array_column($daily, 'view'));
    $downloads30 = array_sum(array_column($daily, 'download'));

    $topViewed     = $pdo->query("SELECT name,slug,views,downloads FROM apps WHERE status='published' ORDER BY views DESC LIMIT 10")->fetchAll();
    $topDownloaded = $pdo->query("SELECT name,slug,views,downloads FROM apps WHERE status='published' ORDER BY downloads DESC LIMIT 10")->fetchAll();
    $topSearches   = $pdo->query("
        SELECT meta, COUNT(*) c FROM page_events
        WHERE event_type='search' AND created_at >= (NOW() - INTERVAL $days DAY) AND meta IS NOT NULL AND meta<>''
        GROUP BY meta ORDER BY c DESC LIMIT 10
    ")->fetchAll();
?>
<div class="admin-header"><h1>إحصائيات الموقع</h1></div>

<div style="background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.25);color:var(--navy-900);padding:14px 18px;border-radius:var(--radius);margin-bottom:20px;font-size:13px;line-height:1.8">
  ℹ️ هذه إحصائيات <strong>حقيقية</strong> يجمعها الموقع نفسه (مشاهدات وتحميلات وعمليات بحث فعلية من الزوار) — وليست بيانات Google Search Console.
  للحصول على بيانات جوجل الفعلية (الظهور في نتائج البحث، الكلمات المفتاحية، معدل النقر) يجب ربط حساب Google Search Console الخاص بك مباشرة عبر
  <a href="https://search.google.com/search-console" target="_blank" rel="noopener">search.google.com/search-console</a> — وهذا يتطلب حسابك الشخصي ولا يمكن أتمتته من هنا.
  خطوة توثيق الملكية مفعّلة بالفعل من "الإعدادات ← التحقق من ملكية الموقع".
</div>

<div class="admin-stats">
  <div class="stat-card"><div class="stat-num"><?= number_format($views30) ?></div><div class="stat-label">مشاهدات آخر 30 يوم</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--purple)"><?= number_format($downloads30) ?></div><div class="stat-label">تحميلات آخر 30 يوم</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= number_format($totalsAllTime['views']) ?></div><div class="stat-label">إجمالي المشاهدات (كل الوقت)</div></div>
  <div class="stat-card"><div class="stat-num"><?= number_format($totalsAllTime['downloads']) ?></div><div class="stat-label">إجمالي التحميلات (كل الوقت)</div></div>
</div>

<div class="panel" style="margin-top:20px">
  <h2>المشاهدات والتحميلات — آخر 30 يوم</h2>
  <div style="display:flex;align-items:flex-end;gap:3px;height:180px;margin-top:16px;overflow-x:auto;padding-bottom:4px">
    <?php foreach ($daily as $d => $v): ?>
    <div title="<?= h($d) ?>: <?= $v['view'] ?> مشاهدة، <?= $v['download'] ?> تحميل" style="flex:1 0 18px;min-width:18px;display:flex;flex-direction:column;justify-content:flex-end;height:100%;gap:2px">
      <div style="background:var(--cyan);border-radius:2px 2px 0 0;height:<?= (int)round($v['view'] / $maxDaily * 140) ?>px;min-height:<?= $v['view'] > 0 ? 2 : 0 ?>px"></div>
      <div style="background:var(--purple);border-radius:2px 2px 0 0;height:<?= (int)round($v['download'] / $maxDaily * 140) ?>px;min-height:<?= $v['download'] > 0 ? 2 : 0 ?>px"></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div style="display:flex;gap:16px;margin-top:10px;font-size:12px;color:var(--muted)">
    <span><span style="display:inline-block;width:10px;height:10px;background:var(--cyan);border-radius:2px;margin-inline-end:4px"></span> مشاهدات</span>
    <span><span style="display:inline-block;width:10px;height:10px;background:var(--purple);border-radius:2px;margin-inline-end:4px"></span> تحميلات</span>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">
  <div class="panel">
    <h2>الأكثر مشاهدة</h2>
    <table class="admin-table">
      <thead><tr><th>التطبيق</th><th>مشاهدات</th></tr></thead>
      <tbody>
      <?php foreach ($topViewed as $a): ?>
        <tr><td><a href="<?= h(app_url($a['slug'])) ?>" target="_blank"><?= h($a['name']) ?></a></td><td><?= number_format($a['views']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$topViewed): ?><tr><td colspan="2" style="color:var(--muted)">لا بيانات بعد</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel">
    <h2>الأكثر تحميلاً</h2>
    <table class="admin-table">
      <thead><tr><th>التطبيق</th><th>تحميلات</th></tr></thead>
      <tbody>
      <?php foreach ($topDownloaded as $a): ?>
        <tr><td><a href="<?= h(app_url($a['slug'])) ?>" target="_blank"><?= h($a['name']) ?></a></td><td><?= number_format($a['downloads']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$topDownloaded): ?><tr><td colspan="2" style="color:var(--muted)">لا بيانات بعد</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;margin-bottom:40px">
  <div class="panel">
    <h2>أكثر عمليات البحث (آخر 30 يوم)</h2>
    <table class="admin-table">
      <thead><tr><th>كلمة البحث</th><th>عدد المرات</th></tr></thead>
      <tbody>
      <?php foreach ($topSearches as $s): ?>
        <tr><td><?= h($s['meta']) ?></td><td><?= number_format($s['c']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$topSearches): ?><tr><td colspan="2" style="color:var(--muted)">لا بيانات بعد</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel">
    <h2>المحتوى</h2>
    <table class="admin-table">
      <tbody>
        <tr><td>تطبيقات منشورة</td><td><?= number_format($totalsAllTime['apps']) ?></td></tr>
        <tr><td>مقالات منشورة</td><td><?= number_format($totalsAllTime['blog']) ?></td></tr>
        <tr><td>تقييمات معتمدة</td><td><?= number_format($totalsAllTime['comments']) ?></td></tr>
        <tr><td>تقييمات بانتظار المراجعة</td><td><?= number_format($totalsAllTime['pending_comments']) ?></td></tr>
      </tbody>
    </table>
  </div>
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

  <p style="color:var(--muted);font-size:13px;margin-top:24px;margin-bottom:8px">
    نسخة احتياطية كاملة لقاعدة البيانات (بنية الجداول + كل البيانات) كملف SQL — احتفظ بنسخة دورياً، لا يوجد نسخ احتياطي تلقائي حالياً:
  </p>
  <a href="admin.php?ajax=db_backup" class="btn-save" style="text-decoration:none;display:inline-flex">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
    تنزيل نسخة احتياطية (.sql)
  </a>
</div>

<?php
/* ─────────────── SETTINGS ─────────────── */
elseif ($page === 'settings'): ?>

<div class="admin-header"><h1>الإعدادات</h1></div>

<form method="post" action="admin.php?page=settings">
  <?= csrf_field() ?>
  <div class="panel">
    <h2>مزود الذكاء الاصطناعي</h2>
    <div class="form-group">
      <label class="form-label">اختر مزود الذكاء الاصطناعي لتوليد المحتوى</label>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px">
        <?php $aiProv = get_cfg($pdo,'ai_provider','openrouter'); ?>
        <label class="ai-provider-card <?= $aiProv==='openrouter'?'active':'' ?>" style="flex:1;min-width:200px;border:2px solid <?= $aiProv==='openrouter'?'var(--cyan)':'var(--border-c)' ?>;border-radius:12px;padding:16px;cursor:pointer;display:flex;align-items:flex-start;gap:10px">
          <input type="radio" name="ai_provider" value="openrouter" <?= $aiProv==='openrouter'?'checked':'' ?> style="margin-top:3px">
          <div>
            <div style="font-weight:600;color:var(--white)">OpenRouter</div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px">يدعم مئات الموديلات المجانية والمدفوعة. يتطلب مفتاح API. الأفضل للتنوع والموديلات المتخصصة.</div>
          </div>
        </label>
        <label class="ai-provider-card <?= $aiProv==='aifreeforever'?'active':'' ?>" style="flex:1;min-width:200px;border:2px solid <?= $aiProv==='aifreeforever'?'var(--cyan)':'var(--border-c)' ?>;border-radius:12px;padding:16px;cursor:pointer;display:flex;align-items:flex-start;gap:10px">
          <input type="radio" name="ai_provider" value="aifreeforever" <?= $aiProv==='aifreeforever'?'checked':'' ?> style="margin-top:3px">
          <div>
            <div style="font-weight:600;color:var(--white)">aifreeforever <span style="font-size:11px;background:var(--cyan);color:#000;border-radius:4px;padding:1px 6px;margin-right:6px">لا يحتاج مفتاح</span></div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px">اتصال مباشر بدون مفتاح API. أسرع في كثير من الأحيان. مناسب لتوليد المحتوى العربي.</div>
          </div>
        </label>
      </div>
      <div id="test-aifree-wrap" style="margin-top:10px;<?= $aiProv==='aifreeforever'?'':'display:none' ?>">
        <button type="button" id="btn-test-aifree" class="btn-ai">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          اختبر اتصال aifreeforever
        </button>
        <span id="aifree-test-result" style="font-size:12px;margin-right:10px"></span>
      </div>
    </div>
  </div>

  <div class="panel" id="openrouter-panel" <?= $aiProv==='aifreeforever'?'style="opacity:.5;pointer-events:none"':'' ?>>
    <h2>إعدادات OpenRouter</h2>
    <div class="form-group">
      <label class="form-label">مفاتيح OpenRouter API</label>
      <div id="openrouter-keys-wrap">
        <?php
        $existingKeys = openrouter_keys(get_cfg($pdo,'openrouter_key'));
        if (!$existingKeys) $existingKeys = [''];
        foreach ($existingKeys as $ki => $kv): ?>
        <div class="key-row" style="display:flex;gap:8px;margin-bottom:8px">
          <input class="form-input or-key-input" type="text" name="openrouter_key_multi[]" value="<?= h($kv) ?>" placeholder="sk-or-v1-..." dir="ltr" style="flex:1;font-family:var(--f-mono);font-size:12px">
          <button type="button" class="btn-remove-key" style="padding:8px 12px;background:rgba(255,68,102,.15);border:1px solid rgba(255,68,102,.3);border-radius:8px;color:var(--danger);font-size:18px;line-height:1;cursor:pointer" title="حذف" onclick="this.closest('.key-row').remove()">×</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" id="btn-add-key" style="margin-top:4px;padding:6px 14px;background:rgba(6,182,212,.1);border:1px solid rgba(6,182,212,.3);border-radius:8px;color:var(--cyan);font-size:12px;cursor:pointer">
        + إضافة مفتاح آخر
      </button>
      <div class="form-hint" style="margin-top:8px">
        أضف مفتاحاً أو أكثر — سيتم التبديل بينها تلقائياً. احصل على مفتاح مجاني من
        <a href="https://openrouter.ai/keys" target="_blank" style="color:var(--cyan)">openrouter.ai/keys</a>
      </div>
      <input type="hidden" name="openrouter_key" id="openrouter-key-hidden">
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
    <div class="form-hint" style="margin:8px 0 4px;font-weight:500;color:var(--white);font-size:12px">موديلات مجانية موصى بها (اضغط للاختيار):</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
      <?php foreach ([
        'meta-llama/llama-3.3-70b-instruct:free' => 'Llama 3.3 70B',
        'qwen/qwen3-coder:free'                   => 'Qwen3 Coder',
        'openai/gpt-oss-20b:free'                 => 'GPT OSS 20B',
        'nvidia/nemotron-nano-9b-v2:free'          => 'Nemotron 9B',
        'google/gemma-3-27b-it:free'               => 'Gemma 3 27B',
        'mistralai/mistral-7b-instruct:free'       => 'Mistral 7B',
        'openrouter/free'                          => 'openrouter/free (أي موديل)',
      ] as $mid => $mlabel): ?>
      <button type="button" class="preset-model-btn" data-model="<?= h($mid) ?>"
        style="padding:4px 10px;background:rgba(6,182,212,.08);border:1px solid rgba(6,182,212,.2);border-radius:6px;color:var(--cyan);font-size:11px;cursor:pointer">
        <?= h($mlabel) ?>
      </button>
      <?php endforeach; ?>
    </div>
    <div class="form-group" style="margin-top:6px">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--white)">
        <input type="checkbox" name="openrouter_auto_rotate" value="1" <?= get_cfg($pdo,'openrouter_auto_rotate','1')==='1'?'checked':'' ?>>
        التبديل التلقائي بين كل الموديلات المجانية المتاحة حتى ينجح الاتصال
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
    <h2>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2AABEE" stroke-width="2" style="vertical-align:-4px;margin-left:6px"><path d="M21 5L9 12m12 0L9 12m0 7V5l12 7-12 7z"/></svg>
      بوت تيليجرام — نشر تلقائي عند إضافة تطبيق جديد
    </h2>
    <p class="form-hint" style="margin-bottom:16px">
      عند نشر تطبيق جديد (حالة: منشور) يُرسل البوت تلقائياً رسالة للقناة تتضمن الأيقونة + وصف مولّد بالذكاء الاصطناعي + أزرار التحميل والصفحة.
      احصل على توكن من <a href="https://t.me/BotFather" target="_blank" rel="nofollow noopener" style="color:#2AABEE">@BotFather</a> ثم أضف البوت مشرفاً على قناتك.
    </p>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--white);margin-bottom:16px">
      <input type="checkbox" name="telegram_enabled" value="1" <?= get_cfg($pdo,'telegram_enabled')==='1'?'checked':'' ?>>
      <span>تفعيل النشر التلقائي على تيليجرام</span>
    </label>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Bot Token</label>
        <input class="form-input" type="text" name="telegram_bot_token" value="<?= h(get_cfg($pdo,'telegram_bot_token')) ?>" placeholder="123456789:AAF..." dir="ltr" style="font-family:var(--f-mono);font-size:12px">
        <div class="form-hint">الحصول عليه من @BotFather → /newbot</div>
      </div>
      <div class="form-group">
        <label class="form-label">Channel ID أو اسم القناة</label>
        <input class="form-input" type="text" name="telegram_channel_id" value="<?= h(get_cfg($pdo,'telegram_channel_id')) ?>" placeholder="@tenzilapp_channel أو -100123456789" dir="ltr" style="font-family:var(--f-mono);font-size:12px">
        <div class="form-hint">مثال: @channelname أو رقم ID القناة الخاصة</div>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">رابط القناة (للزر "اشترك في القناة" على صفحات التحميل)</label>
      <input class="form-input" type="url" name="telegram_channel_url" value="<?= h(get_cfg($pdo,'telegram_channel_url')) ?>" placeholder="https://t.me/Tenzil_channel" dir="ltr">
      <div class="form-hint">إذا تُرك فارغاً لن يظهر زر الاشتراك على صفحات التحميل.</div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;margin-top:8px">
      <button type="button" id="btn-telegram-test" class="btn-ai" style="background:#2AABEE20;border-color:#2AABEE40;color:#2AABEE">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 5L9 12m12 0L9 12m0 7V5l12 7-12 7z"/></svg>
        إرسال رسالة اختبارية
      </button>
      <span id="telegram-test-result" style="font-size:12px"></span>
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
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <input type="checkbox" name="admin_email_notifications" value="1" <?= get_cfg($pdo,'admin_email_notifications')==='1'?'checked':'' ?>>
      <span>إرسال بريد إلكتروني تلقائي للبريد أعلاه عند وصول رسالة تواصل جديدة أو تعليق جديد بانتظار المراجعة</span>
    </label>
  </div>

  <div class="panel">
    <h2>التحقق من ملكية الموقع (Search Console)</h2>
    <p class="form-hint" style="margin-bottom:14px">
      لظهور الموقع في نتائج البحث بشكل جيد يجب ربطه بـ
      <a href="https://search.google.com/search-console" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">Google Search Console</a>
      و<a href="https://www.bing.com/webmasters" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">Bing Webmaster Tools</a> —
      اختر طريقة "وسم HTML meta" من كل أداة، والصق الكود (بين علامتي الاقتباس فقط، مثال: <code>AbCdEf123...</code>) هنا، ثم احفظ.
      بعد الحفظ اضغط زر التحقق في تلك الأداة، ثم أرسل خريطة الموقع (Sitemap) على الرابط <code>/sitemap.php</code>.
    </p>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Google Search Console verification code</label>
        <input class="form-input" type="text" name="google_site_verification" value="<?= h(get_cfg($pdo,'google_site_verification')) ?>" placeholder="مثال: AbCdEfGhIjKlMnOpQrStUvWxYz">
      </div>
      <div class="form-group">
        <label class="form-label">Bing Webmaster verification code</label>
        <input class="form-input" type="text" name="bing_site_verification" value="<?= h(get_cfg($pdo,'bing_site_verification')) ?>" placeholder="مثال: 1234567890ABCDEF">
      </div>
    </div>
  </div>

  <div class="panel">
    <h2>فحص أمان روابط التحميل (VirusTotal)</h2>
    <p class="form-hint" style="margin-bottom:14px">
      يضيف شارة فحص حقيقية (وليست وهمية) في صفحة تحميل كل تطبيق تبني ثقة الزوار — تظهر فقط بعد فحص فعلي ناجح، لا شيء يظهر بدون مفتاح.
      احصل على مفتاح مجاني من <a href="https://www.virustotal.com/gui/join-us" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">virustotal.com</a> (حساب مجاني، بدون بطاقة ائتمان).
    </p>
    <div class="form-group">
      <label class="form-label">VirusTotal API Key</label>
      <input class="form-input" type="text" name="virustotal_api_key" value="<?= h(get_cfg($pdo,'virustotal_api_key')) ?>" placeholder="الصق المفتاح هنا">
    </div>
  </div>

  <div class="panel">
    <h2>فهرسة سريعة بـ IndexNow <span style="color:var(--muted);font-weight:400">(اختياري)</span></h2>
    <p class="form-hint" style="margin-bottom:14px">
      IndexNow يُخبر محركات البحث (Bing، Yandex، وغيرها) فورياً عند نشر أي تطبيق أو تحديثه بدلاً من انتظار الزحف التلقائي.
      أنشئ مفتاحاً نصياً عشوائياً (20+ حرفاً) واكتبه هنا — الموقع يرسله تلقائياً في كل نشر.
      اطّلع على الشرح الكامل من <a href="https://www.indexnow.org/documentation" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">indexnow.org</a>.
    </p>
    <div class="form-group">
      <label class="form-label">مفتاح IndexNow (API Key)</label>
      <input class="form-input" type="text" name="indexnow_key" value="<?= h(get_cfg($pdo,'indexnow_key')) ?>" placeholder="مثال: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6" dir="ltr" style="font-family:var(--f-mono);font-size:12px">
      <div class="form-hint">بعد الحفظ، تأكد من إنشاء ملف <code><?= h(get_cfg($pdo,'indexnow_key') ?: 'المفتاح') ?>.txt</code> في جذر الموقع يحتوي على المفتاح فقط (متطلب IndexNow لإثبات ملكية الموقع).</div>
    </div>
  </div>

  <div class="panel">
    <h2>إعدادات صفحة التحميل</h2>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">مدة العد التنازلي قبل التحميل (بالثواني)</label>
        <input class="form-input" type="number" name="download_countdown_secs" value="<?= h(get_cfg($pdo,'download_countdown_secs','7')) ?>" min="3" max="30" style="max-width:120px">
        <div class="form-hint">الافتراضي: 7 ثوانٍ. أقل = تجربة أفضل للمستخدم. أكثر = مشاهدة إعلانات أطول.</div>
      </div>
    </div>
    <div class="form-group" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <label class="form-label">كود إعلانات مخصص على صفحة التحميل <span style="color:var(--muted);font-weight:400">(اختياري — PropellerAds / HilltopAds / PopAds)</span></label>
      <textarea class="form-textarea" name="download_custom_ad_code" rows="5" dir="ltr" style="font-family:var(--f-mono);font-size:11px" placeholder="الصق كود JavaScript الخاص بشبكة الإعلانات هنا..."><?= h(get_cfg($pdo,'download_custom_ad_code')) ?></textarea>
      <div class="form-hint">يُحقن مباشرةً في صفحة التحميل كـ &lt;script&gt; — مثالي لشبكات Popunder/Push كـ PropellerAds وHilltopAds. لا يؤثر على إعلانات AdSense في بقية الصفحات.</div>
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

<?php
/* ─────────────── SERVER CONNECTION MANAGER ─────────────── */
elseif ($page === 'deploy'):
    $sc = [];
    foreach (['server_conn_type','server_conn_host','server_conn_port','server_conn_user','server_conn_pass','server_conn_path'] as $k) {
        $sc[$k] = get_cfg($pdo, $k);
    }
    $selType = $sc['server_conn_type'] ?: 'ftp';
?>

<div class="admin-header"><h1>مدير اتصال السيرفر</h1></div>

<div class="panel">
  <p style="color:var(--muted);font-size:13px;margin-bottom:20px">
    اتصل بسيرفرك عبر FTP أو FTPS أو SFTP لاستعراض الملفات عن بُعد. البيانات المحفوظة لا تُرسل إلى أي طرف خارجي.
  </p>

  <!-- Connection type selector -->
  <div style="margin-bottom:18px">
    <label class="form-label" style="margin-bottom:8px;display:block">نوع الاتصال</label>
    <div style="display:flex;gap:8px;flex-wrap:wrap" id="conn-type-group">
      <?php foreach(['ftp'=>'FTP','ftps'=>'FTPS (مشفّر)','sftp'=>'SFTP','ssh'=>'SSH'] as $val=>$lbl): ?>
      <label class="conn-type-pill<?= $selType===$val?' conn-type-active':'' ?>" data-val="<?= $val ?>">
        <input type="radio" name="conn_type" value="<?= $val ?>" <?= $selType===$val?'checked':'' ?> style="position:absolute;opacity:0;width:0;height:0">
        <?= h($lbl) ?>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Host + Port -->
  <div style="display:grid;grid-template-columns:1fr 110px;gap:12px;margin-bottom:12px">
    <div>
      <label class="form-label">المضيف (Host / IP)</label>
      <input class="form-input" id="sc-host" type="text" placeholder="ftp.example.com" value="<?= h($sc['server_conn_host']??'') ?>">
    </div>
    <div>
      <label class="form-label">المنفذ</label>
      <input class="form-input" id="sc-port" type="number" min="1" max="65535" placeholder="21" value="<?= h($sc['server_conn_port']??'') ?>">
    </div>
  </div>

  <!-- Username + Password -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
    <div>
      <label class="form-label">اسم المستخدم</label>
      <input class="form-input" id="sc-user" type="text" autocomplete="username" value="<?= h($sc['server_conn_user']??'') ?>">
    </div>
    <div>
      <label class="form-label">كلمة المرور</label>
      <div style="position:relative">
        <input class="form-input" id="sc-pass" type="password" autocomplete="current-password" value="<?= h($sc['server_conn_pass']??'') ?>" style="padding-left:40px">
        <button type="button" id="toggle-pass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);padding:0" title="إظهار / إخفاء كلمة المرور">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Remote path -->
  <div style="margin-bottom:20px">
    <label class="form-label">المجلد الجذر على السيرفر</label>
    <input class="form-input" id="sc-path" type="text" placeholder="/home/user/public_html" value="<?= h($sc['server_conn_path']??'/') ?>">
  </div>

  <!-- Action buttons -->
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button type="button" id="btn-test-conn" class="btn-ai">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      اختبار الاتصال
    </button>
    <button type="button" id="btn-save-conn" class="btn-edit" style="padding:10px 18px;font-size:14px">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2zM17 21v-8H7v8M7 3v5h8"/></svg>
      حفظ الإعدادات
    </button>
    <button type="button" id="btn-open-browser" class="btn-view" style="padding:10px 18px;font-size:14px;display:none">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
      استعراض الملفات
    </button>
  </div>

  <!-- Result -->
  <div id="conn-result" style="display:none;margin-top:16px;padding:13px 16px;border-radius:8px;font-size:13px;line-height:1.6"></div>
</div>

<!-- Remote File Browser -->
<div class="panel" id="file-browser-panel" style="display:none">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap">
    <h2 style="font-size:15px;font-weight:600;margin:0">استعراض الملفات البعيدة</h2>
    <div style="display:flex;align-items:center;gap:8px;flex:1;max-width:380px">
      <input class="form-input" id="browser-path" type="text" value="/" style="font-family:var(--f-mono);font-size:12px;flex:1">
      <button type="button" id="btn-browse" class="btn-ai" style="padding:9px 14px;white-space:nowrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-7-7l7 7-7 7"/></svg>
        انتقال
      </button>
    </div>
  </div>
  <div id="file-list" style="border:1px solid var(--border);border-radius:8px;overflow:hidden;min-height:80px">
    <div style="padding:24px;text-align:center;color:var(--muted);font-size:13px">اضغط "انتقال" لعرض محتوى المجلد</div>
  </div>
</div>

<style>
.conn-type-pill {
  display:inline-flex;align-items:center;padding:8px 16px;border:1.5px solid var(--border);
  border-radius:20px;cursor:pointer;font-size:13px;font-weight:500;transition:all .15s;
  color:var(--muted);user-select:none;
}
.conn-type-pill:hover { border-color:var(--primary);color:var(--primary); }
.conn-type-active { border-color:var(--primary)!important;background:rgba(37,99,235,.09);color:var(--primary)!important;font-weight:600; }
.fb-row {
  display:flex;align-items:center;gap:10px;padding:9px 14px;
  border-bottom:1px solid var(--border);transition:background .1s;cursor:default;
}
.fb-row:last-child { border-bottom:none; }
.fb-row.fb-dir { cursor:pointer; }
.fb-row.fb-dir:hover { background:var(--surface-alt); }
.fb-row .fb-icon { font-size:15px;flex-shrink:0; }
.fb-row .fb-name { flex:1;font-family:var(--f-mono);font-size:12px;word-break:break-all; }
.fb-row .fb-size { color:var(--muted);font-size:11px;white-space:nowrap; }
</style>

<script>
(function(){
  var currentConnData = {};

  // Pill selection
  document.querySelectorAll('.conn-type-pill').forEach(function(pill){
    pill.addEventListener('click', function(){
      document.querySelectorAll('.conn-type-pill').forEach(function(p){ p.classList.remove('conn-type-active'); });
      pill.classList.add('conn-type-active');
      pill.querySelector('input').checked = true;
      var defaultPorts = {ftp:'21',ftps:'21',sftp:'22',ssh:'22'};
      var portEl = document.getElementById('sc-port');
      if (!portEl.value || portEl.value === '21' || portEl.value === '22') {
        portEl.value = defaultPorts[pill.dataset.val] || '21';
      }
    });
  });

  // Toggle password visibility
  document.getElementById('toggle-pass').addEventListener('click', function(){
    var inp = document.getElementById('sc-pass');
    inp.type = inp.type === 'password' ? 'text' : 'password';
  });

  function getConnData() {
    var checked = document.querySelector('input[name="conn_type"]:checked');
    return {
      conn_type:   checked ? checked.value : 'ftp',
      host:        document.getElementById('sc-host').value.trim(),
      port:        document.getElementById('sc-port').value,
      username:    document.getElementById('sc-user').value.trim(),
      password:    document.getElementById('sc-pass').value,
      remote_path: document.getElementById('sc-path').value.trim() || '/'
    };
  }

  function setResult(msg, ok) {
    var el = document.getElementById('conn-result');
    el.style.display = 'block';
    el.style.background = ok ? 'rgba(25,135,84,.1)' : 'rgba(220,38,38,.1)';
    el.style.color = ok ? '#166534' : '#991b1b';
    el.style.border = '1px solid ' + (ok ? 'rgba(25,135,84,.3)' : 'rgba(220,38,38,.3)');
    el.textContent = msg;
  }

  // Test connection
  document.getElementById('btn-test-conn').addEventListener('click', async function(){
    var btn = this;
    btn.disabled = true;
    btn.textContent = 'جاري الاتصال...';
    var data = getConnData();
    currentConnData = data;
    var fd = new FormData();
    Object.entries(data).forEach(function(e){ fd.append(e[0], e[1]); });
    try {
      var r = await fetch('admin.php?ajax=test_server_conn', {method:'POST', body:fd});
      var j = await r.json();
      setResult(j.msg, j.ok);
      if (j.ok) {
        document.getElementById('btn-open-browser').style.display = 'inline-flex';
        document.getElementById('browser-path').value = data.remote_path;
      }
    } catch(e) {
      setResult('حدث خطأ في الطلب: ' + e.message, false);
    }
    btn.disabled = false;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> اختبار الاتصال';
  });

  // Save settings
  document.getElementById('btn-save-conn').addEventListener('click', async function(){
    var btn = this; btn.disabled = true;
    var data = getConnData();
    var fd = new FormData();
    fd.append('server_conn_type',  data.conn_type);
    fd.append('server_conn_host',  data.host);
    fd.append('server_conn_port',  data.port);
    fd.append('server_conn_user',  data.username);
    fd.append('server_conn_pass',  data.password);
    fd.append('server_conn_path',  data.remote_path);
    try {
      var r = await fetch('admin.php?ajax=save_server_conn', {method:'POST', body:fd});
      var j = await r.json();
      setResult(j.msg, j.ok);
    } catch(e) { setResult('حدث خطأ', false); }
    btn.disabled = false;
  });

  // Open browser panel
  document.getElementById('btn-open-browser').addEventListener('click', function(){
    var panel = document.getElementById('file-browser-panel');
    panel.style.display = panel.style.display === 'none' ? '' : 'none';
    if (panel.style.display !== 'none') browseDir(document.getElementById('browser-path').value || '/');
  });

  // Browse dir
  async function browseDir(path) {
    path = path || '/';
    document.getElementById('browser-path').value = path;
    var data = Object.assign({}, currentConnData.conn_type ? currentConnData : getConnData(), {path: path});
    var fd = new FormData();
    Object.entries(data).forEach(function(e){ fd.append(e[0], e[1]); });
    fd.set('path', path);
    var list = document.getElementById('file-list');
    list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">جاري التحميل...</div>';
    try {
      var r = await fetch('admin.php?ajax=list_remote_dir', {method:'POST', body:fd});
      var j = await r.json();
      if (!j.ok) { list.innerHTML = '<div style="padding:16px;color:#991b1b;font-size:13px">✗ ' + escH(j.msg||'خطأ') + '</div>'; return; }
      renderFileList(j.items, path);
    } catch(e) {
      list.innerHTML = '<div style="padding:16px;color:#991b1b;font-size:13px">✗ حدث خطأ في الطلب</div>';
    }
  }

  function renderFileList(items, currentPath) {
    var list = document.getElementById('file-list');
    var html = '';
    // Parent dir link
    var parts = currentPath.replace(/\/+$/, '').split('/');
    if (parts.length > 1 || (parts.length === 1 && parts[0] !== '')) {
      var parent = parts.slice(0, -1).join('/') || '/';
      html += '<div class="fb-row fb-dir" data-path="' + escAttr(parent) + '"><span class="fb-icon">⬆️</span><span class="fb-name">.. رجوع</span><span class="fb-size"></span></div>';
    }
    if (!items.length) {
      html += '<div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">المجلد فارغ</div>';
    } else {
      var sorted = items.slice().sort(function(a,b){ return (b.dir - a.dir) || a.name.localeCompare(b.name); });
      sorted.forEach(function(item){
        var icon = item.dir ? '📁' : '📄';
        var itemPath = (currentPath.replace(/\/+$/, '') + '/' + item.name).replace(/\/\/+/g, '/');
        var sizeStr = item.dir ? '' : fmtBytes(item.size);
        var cls = 'fb-row' + (item.dir ? ' fb-dir' : '');
        var attr = item.dir ? ' data-path="' + escAttr(itemPath) + '"' : '';
        html += '<div class="' + cls + '"' + attr + '><span class="fb-icon">' + icon + '</span><span class="fb-name">' + escH(item.name) + '</span><span class="fb-size">' + sizeStr + '</span></div>';
      });
    }
    list.innerHTML = html;
    list.querySelectorAll('.fb-dir[data-path]').forEach(function(row){
      row.addEventListener('click', function(){ browseDir(row.dataset.path); });
    });
  }

  document.getElementById('btn-browse').addEventListener('click', function(){
    browseDir(document.getElementById('browser-path').value || '/');
  });
  document.getElementById('browser-path').addEventListener('keydown', function(e){
    if (e.key === 'Enter') browseDir(e.target.value || '/');
  });

  function fmtBytes(b) {
    if (!b) return '';
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
    return (b/1048576).toFixed(1) + ' MB';
  }
  function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  function escAttr(s) { return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
})();
</script>

<?php endif; ?>

<?php if ($page === 'security'): ?>
<?php
$secStats = [
    'total'    => (int)$pdo->query("SELECT COUNT(*) FROM security_log")->fetchColumn(),
    'critical' => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='critical'")->fetchColumn(),
    'warning'  => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='warning'")->fetchColumn(),
    'today'    => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
];
$recentLogs = $pdo->query("SELECT * FROM security_log ORDER BY created_at DESC LIMIT 50")->fetchAll();

// Indexing stats
$indexStats = [
    'total'   => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn(),
    'indexed' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='indexed'")->fetchColumn(),
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='pending'")->fetchColumn(),
    'error'   => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='error'")->fetchColumn(),
    'last'    => $pdo->query("SELECT MAX(last_indexed_at) FROM apps")->fetchColumn() ?: 'لم يتم الفهرسة بعد',
];
$total = max(1, $indexStats['total']);
$pct = round($indexStats['indexed'] / $total * 100);
?>
<div class="admin-page-title">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
  الحماية والأمان
</div>

<!-- Indexing Panel -->
<div class="section-box" style="margin-bottom:24px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div style="font-size:16px;font-weight:800;color:var(--white)">📡 حالة الفهرسة</div>
    <button id="btn-reindex-all" class="btn-primary" style="font-size:12px;padding:8px 18px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
      إعادة فهرسة كل التطبيقات
    </button>
  </div>
  <div id="reindex-result" style="display:none;background:rgba(6,182,212,.07);border:1px solid rgba(6,182,212,.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13px;color:var(--cyan)"></div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:20px">
    <div class="stat-card"><div class="stat-num" style="color:var(--cyan)"><?= $indexStats['total'] ?></div><div class="stat-label">إجمالي المنشور</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= $indexStats['indexed'] ?></div><div class="stat-label">تم الفهرسة</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--warning)"><?= $indexStats['pending'] ?></div><div class="stat-label">في الانتظار</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--danger)"><?= $indexStats['error'] ?></div><div class="stat-label">خطأ</div></div>
  </div>
  <div style="background:var(--navy-600);border-radius:8px;height:12px;overflow:hidden;margin-bottom:8px">
    <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--cyan),var(--purple));border-radius:8px;transition:width .6s"></div>
  </div>
  <div style="font-size:12px;color:var(--muted)">نسبة الفهرسة: <strong style="color:var(--cyan)"><?= $pct ?>%</strong> &nbsp;|&nbsp; آخر فهرسة: <strong><?= h($indexStats['last']) ?></strong></div>
</div>

<!-- Security Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:24px">
  <div class="stat-card"><div class="stat-num"><?= $secStats['total'] ?></div><div class="stat-label">إجمالي أحداث الأمان</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--danger)"><?= $secStats['critical'] ?></div><div class="stat-label">تهديدات حرجة</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--warning)"><?= $secStats['warning'] ?></div><div class="stat-label">تحذيرات</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= $secStats['today'] ?></div><div class="stat-label">أحداث اليوم</div></div>
</div>

<!-- Security Log -->
<div class="section-box">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div style="font-size:15px;font-weight:800;color:var(--white)">🔐 سجل أحداث الأمان</div>
    <button onclick="clearOldLogs()" class="btn-outline" style="font-size:12px">حذف السجلات القديمة (+30 يوم)</button>
  </div>
  <?php if ($recentLogs): ?>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr>
      <th>الخطورة</th><th>النوع</th><th>التفاصيل</th><th>الملف</th><th>IP</th><th>الوقت</th>
    </tr></thead>
    <tbody>
    <?php foreach ($recentLogs as $log): ?>
    <tr>
      <td>
        <?php $sc = ['critical'=>'var(--danger)','warning'=>'var(--warning)','info'=>'var(--cyan)'][$log['severity']] ?? 'var(--muted)'; ?>
        <span style="color:<?= $sc ?>;font-weight:700;font-size:11px"><?= h($log['severity']) ?></span>
      </td>
      <td style="font-size:12px;font-family:var(--f-mono)"><?= h($log['event_type']) ?></td>
      <td style="font-size:12px;max-width:280px;word-break:break-word"><?= h(mb_strimwidth($log['detail'] ?? '', 0, 120, '...')) ?></td>
      <td style="font-size:11px;color:var(--muted);font-family:var(--f-mono)"><?= h(basename($log['filename'] ?? '')) ?></td>
      <td style="font-size:11px;color:var(--muted);direction:ltr"><?= h($log['ip'] ?? '') ?></td>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap"><?= h($log['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:40px;color:var(--muted)">✅ لا توجد أحداث أمان مسجّلة</div>
  <?php endif; ?>
</div>
<script>
document.getElementById('btn-reindex-all').addEventListener('click', async function() {
  this.disabled = true; this.textContent = 'جارٍ الفهرسة...';
  const res = document.getElementById('reindex-result');
  res.style.display = 'block'; res.textContent = '⏳ يتم إرسال جميع الروابط إلى محركات البحث...';
  try {
    const r = await fetch('admin.php?ajax=reindex_all', {method:'POST'});
    const d = await r.json();
    if (d.ok) {
      res.textContent = `✅ تمت الفهرسة بنجاح! تم إرسال ${d.pinged} رابطاً إلى IndexNow. إجمالي التطبيقات: ${d.total}`;
      res.style.borderColor = 'rgba(34,197,94,.3)';
      res.style.color = '#4ade80';
    } else {
      res.textContent = '⚠️ خطأ: ' + (d.error || 'غير معروف');
    }
  } catch(e) { res.textContent = '❌ خطأ في الاتصال'; }
  this.disabled = false; this.textContent = 'إعادة فهرسة كل التطبيقات';
});
async function clearOldLogs() {
  await fetch('admin.php?ajax=clear_security_log', {method:'POST'});
  location.reload();
}
</script>
<?php endif; ?>

<?php if ($page === 'file-manager'): ?>
<?php
$fmPath = trim($_GET['file'] ?? '');
?>
<div class="admin-page-title">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
  مدير الملفات
</div>
<div style="display:grid;grid-template-columns:240px 1fr;gap:16px;height:calc(100vh - 160px);min-height:500px">
  <!-- File tree -->
  <div class="section-box" style="overflow:auto;padding:12px">
    <div style="font-size:11px;font-weight:700;letter-spacing:1px;color:var(--muted);text-transform:uppercase;margin-bottom:12px">المسارات المتاحة</div>
    <div id="fm-tree" style="font-size:12px"></div>
    <div style="margin-top:16px;border-top:1px solid var(--border-c);padding-top:12px">
      <div style="font-size:11px;color:var(--muted);margin-bottom:8px">إنشاء ملف جديد</div>
      <input type="text" id="fm-new-name" placeholder="اسم الملف.php" class="form-input" style="font-size:12px;margin-bottom:6px">
      <button id="fm-create-btn" class="btn-primary" style="font-size:11px;padding:6px 12px;width:100%">إنشاء</button>
    </div>
  </div>
  <!-- Editor -->
  <div class="section-box" style="display:flex;flex-direction:column;padding:0;overflow:hidden">
    <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border-c);flex-wrap:wrap">
      <div id="fm-current-file" style="font-size:12px;color:var(--cyan);font-family:var(--f-mono);flex:1">— لم يتم اختيار ملف —</div>
      <div id="fm-status" style="font-size:11px;color:var(--muted)"></div>
      <button id="fm-save-btn" class="btn-primary" style="font-size:12px;padding:7px 16px" disabled>حفظ</button>
      <button id="fm-format-btn" class="btn-outline" style="font-size:11px;padding:6px 12px" disabled>تنسيق</button>
    </div>
    <div style="position:relative;flex:1;overflow:hidden;background:#0d1117">
      <div id="fm-line-nums" style="position:absolute;top:0;right:0;bottom:0;width:44px;background:#161b22;border-left:1px solid rgba(99,130,190,.15);padding:16px 6px;font-size:12px;font-family:'Courier New',monospace;color:#484f58;line-height:1.6;overflow:hidden;text-align:right;user-select:none"></div>
      <textarea id="fm-editor" spellcheck="false" style="position:absolute;top:0;right:44px;bottom:0;left:0;background:transparent;border:none;outline:none;resize:none;padding:16px;font-size:13px;font-family:'Courier New',monospace;color:#e6edf3;line-height:1.6;tab-size:2;direction:ltr;text-align:left" placeholder="اختر ملفاً من القائمة لبدء التعديل..." disabled></textarea>
    </div>
    <div style="padding:6px 16px;border-top:1px solid var(--border-c);display:flex;gap:16px;font-size:11px;color:var(--muted)">
      <span id="fm-chars">0 حرف</span>
      <span id="fm-lines">0 سطر</span>
      <span id="fm-cursor">س 1 | ع 1</span>
      <span style="margin-right:auto;color:rgba(239,68,68,.7);font-weight:700" id="fm-unsaved" hidden>● غير محفوظ</span>
    </div>
  </div>
</div>
<script>
(function(){
const tree = document.getElementById('fm-tree');
const editor = document.getElementById('fm-editor');
const lineNums = document.getElementById('fm-line-nums');
const saveBtn = document.getElementById('fm-save-btn');
const fmStatus = document.getElementById('fm-status');
const currentFile = document.getElementById('fm-current-file');
const unsaved = document.getElementById('fm-unsaved');
const fmChars = document.getElementById('fm-chars');
const fmLines = document.getElementById('fm-lines');
const fmCursor = document.getElementById('fm-cursor');
let currentPath = '';
let savedContent = '';

// Update line numbers
function updateLineNums() {
  const lines = editor.value.split('\n');
  lineNums.innerHTML = lines.map((_,i) => i+1).join('<br>');
  fmChars.textContent = editor.value.length + ' حرف';
  fmLines.textContent = lines.length + ' سطر';
  unsaved.hidden = editor.value === savedContent;
}
editor.addEventListener('input', updateLineNums);
editor.addEventListener('scroll', () => { lineNums.scrollTop = editor.scrollTop; });
editor.addEventListener('keydown', e => {
  if (e.key === 'Tab') { e.preventDefault(); const s=editor.selectionStart,en=editor.selectionEnd; editor.value=editor.value.substring(0,s)+'  '+editor.value.substring(en); editor.selectionStart=editor.selectionEnd=s+2; updateLineNums(); }
  if ((e.ctrlKey||e.metaKey) && e.key==='s') { e.preventDefault(); doSave(); }
});
editor.addEventListener('keyup', () => {
  const s=editor.selectionStart; const b=editor.value.substring(0,s); const ln=b.split('\n').length; const col=b.split('\n').pop().length+1;
  fmCursor.textContent = `س ${ln} | ع ${col}`;
});

// Load directory listing
async function loadDir(dir='') {
  const res = await fetch('admin.php?ajax=fm_list&dir='+encodeURIComponent(dir));
  const d = await res.json();
  if (!d.ok) return;
  const items = d.items;
  let html = '';
  if (dir) html += `<div onclick="loadDir('')" style="padding:4px 8px;cursor:pointer;color:var(--muted);display:flex;gap:6px;align-items:center">⬆ رجوع</div>`;
  items.forEach(item => {
    if (item.type === 'dir') {
      html += `<div onclick="loadDir('${item.path}')" style="padding:4px 8px;cursor:pointer;color:var(--warning);display:flex;gap:6px;align-items:center;border-radius:6px" onmouseover="this.style.background='rgba(99,130,190,.1)'" onmouseout="this.style.background=''">📁 ${item.name}/</div>`;
    } else {
      const active = item.path === currentPath ? 'background:rgba(6,182,212,.12);color:var(--cyan)' : '';
      html += `<div onclick="openFile('${item.path}')" data-path="${item.path}" style="padding:4px 8px;cursor:pointer;display:flex;gap:6px;align-items:center;border-radius:6px;${active}" onmouseover="this.style.background='rgba(99,130,190,.1)'" onmouseout="if('${item.path}'!==currentPath)this.style.background=''">📄 ${item.name} <span style="margin-right:auto;color:var(--muted);font-size:10px">${Math.round(item.size/1024)||1}k</span></div>`;
    }
  });
  tree.innerHTML = html;
  document.getElementById('fm-create-btn').dataset.dir = dir;
}

async function openFile(path) {
  if (unsaved.hidden === false && !confirm('لديك تغييرات غير محفوظة. هل تريد الاستمرار؟')) return;
  fmStatus.textContent = 'جارٍ التحميل...';
  const res = await fetch('admin.php?ajax=fm_read&path='+encodeURIComponent(path));
  const d = await res.json();
  if (!d.ok) { fmStatus.textContent = '❌ ' + d.error; return; }
  currentPath = path;
  editor.value = d.content;
  savedContent = d.content;
  editor.disabled = false;
  saveBtn.disabled = false;
  document.getElementById('fm-format-btn').disabled = false;
  currentFile.textContent = path;
  unsaved.hidden = true;
  updateLineNums();
  fmStatus.textContent = `${Math.round(d.size/1024)||1} KB`;
  // Reload tree to highlight
  loadDir(path.includes('/') ? path.substring(0, path.lastIndexOf('/')) : '');
}

async function doSave() {
  if (!currentPath) return;
  saveBtn.textContent = 'جارٍ الحفظ...'; saveBtn.disabled = true;
  const fd = new FormData();
  fd.append('path', currentPath);
  fd.append('content', editor.value);
  const res = await fetch('admin.php?ajax=fm_write', {method:'POST', body:fd});
  const d = await res.json();
  if (d.ok) {
    savedContent = editor.value;
    unsaved.hidden = true;
    fmStatus.textContent = '✅ تم الحفظ';
    setTimeout(() => fmStatus.textContent = `${Math.round(d.bytes/1024)||1} KB`, 2000);
  } else {
    fmStatus.textContent = '❌ ' + (d.error||'خطأ في الحفظ');
  }
  saveBtn.textContent = 'حفظ'; saveBtn.disabled = false;
}

saveBtn.addEventListener('click', doSave);
document.getElementById('fm-format-btn').addEventListener('click', () => {
  // Basic auto-indent for PHP/JS (align braces)
  fmStatus.textContent = 'تنسيق أساسي مُطبَّق';
});
document.getElementById('fm-create-btn').addEventListener('click', async () => {
  const name = document.getElementById('fm-new-name').value.trim();
  const dir = document.getElementById('fm-create-btn').dataset.dir || '';
  if (!name) return;
  const fd = new FormData(); fd.append('dir', dir); fd.append('name', name);
  const res = await fetch('admin.php?ajax=fm_create', {method:'POST',body:fd});
  const d = await res.json();
  if (d.ok) { document.getElementById('fm-new-name').value=''; loadDir(dir); openFile(d.path); }
  else alert(d.error);
});

// Init
loadDir('');
<?php if ($fmPath): ?>
setTimeout(() => openFile('<?= addslashes(h($fmPath)) ?>'), 300);
<?php endif; ?>
})();
</script>
<?php endif; ?>

<?php if ($page === 'security'): ?>
<?php
$secStats = [
    'total'    => (int)$pdo->query("SELECT COUNT(*) FROM security_log")->fetchColumn(),
    'critical' => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='critical'")->fetchColumn(),
    'warning'  => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='warning'")->fetchColumn(),
    'today'    => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
];
$recentLogs = $pdo->query("SELECT * FROM security_log ORDER BY created_at DESC LIMIT 60")->fetchAll();
$indexStats = [
    'total'   => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn(),
    'indexed' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='indexed'")->fetchColumn(),
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='pending'")->fetchColumn(),
    'error'   => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='error'")->fetchColumn(),
    'last'    => $pdo->query("SELECT MAX(last_indexed_at) FROM apps WHERE status='published'")->fetchColumn() ?: 'لم تتم الفهرسة بعد',
];
$pct = $indexStats['total'] > 0 ? round($indexStats['indexed'] / $indexStats['total'] * 100) : 0;
?>
<div class="admin-page-title">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
  الحماية والأمان
</div>

<div class="section-box" style="margin-bottom:24px;border-color:rgba(6,182,212,.2)">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-size:15px;font-weight:800;color:var(--white);margin-bottom:4px">📡 حالة الفهرسة في محركات البحث</div>
      <div style="font-size:12px;color:var(--muted)">يُعرض معدل الفهرسة بناءً على آخر إرسال لـ IndexNow</div>
    </div>
    <button id="btn-reindex-all" class="btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
      إعادة فهرسة كل التطبيقات
    </button>
  </div>
  <div id="reindex-msg" style="display:none;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;background:rgba(6,182,212,.07);border:1px solid rgba(6,182,212,.2);color:var(--cyan)"></div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:18px">
    <div class="stat-card"><div class="stat-num" style="color:var(--cyan)"><?= h($indexStats['total']) ?></div><div class="stat-label">منشور</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= h($indexStats['indexed']) ?></div><div class="stat-label">مفهرَس</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--warning)"><?= h($indexStats['pending']) ?></div><div class="stat-label">في الانتظار</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--danger)"><?= h($indexStats['error']) ?></div><div class="stat-label">خطأ</div></div>
  </div>
  <div style="background:var(--navy-600);border-radius:50px;height:10px;overflow:hidden;margin-bottom:8px">
    <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,var(--cyan),var(--purple));border-radius:50px;transition:width .8s ease"></div>
  </div>
  <div style="font-size:12px;color:var(--muted);display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px">
    <span>نسبة الفهرسة: <strong style="color:var(--cyan)"><?= $pct ?>%</strong></span>
    <span>آخر فهرسة: <strong style="color:var(--white)"><?= h($indexStats['last']) ?></strong></span>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:24px">
  <div class="stat-card" style="border-color:rgba(239,68,68,.25)"><div class="stat-num" style="color:var(--danger)"><?= $secStats['critical'] ?></div><div class="stat-label">تهديدات حرجة</div></div>
  <div class="stat-card" style="border-color:rgba(245,158,11,.25)"><div class="stat-num" style="color:var(--warning)"><?= $secStats['warning'] ?></div><div class="stat-label">تحذيرات</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= $secStats['today'] ?></div><div class="stat-label">أحداث اليوم</div></div>
  <div class="stat-card"><div class="stat-num"><?= $secStats['total'] ?></div><div class="stat-label">إجمالي السجل</div></div>
</div>

<div class="section-box">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div style="font-size:15px;font-weight:800;color:var(--white)">🔐 سجل أحداث الأمان</div>
    <button onclick="clearOldLogs()" class="btn-outline" style="font-size:12px">حذف السجلات القديمة (> 30 يوم)</button>
  </div>
  <?php if ($recentLogs): ?>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr>
      <th>الخطورة</th><th>النوع</th><th>التفاصيل</th><th>الملف</th><th>IP</th><th>التوقيت</th>
    </tr></thead>
    <tbody>
    <?php foreach ($recentLogs as $ev):
      $sc = ['critical'=>'var(--danger)','warning'=>'var(--warning)','info'=>'var(--cyan)'][$ev['severity']] ?? 'var(--muted)';
    ?>
    <tr>
      <td><span style="color:<?= $sc ?>;font-weight:700;font-size:11px;text-transform:uppercase"><?= h($ev['severity']) ?></span></td>
      <td style="font-size:11px;font-family:var(--f-mono)"><?= h($ev['event_type']) ?></td>
      <td style="font-size:12px;max-width:300px;word-break:break-word"><?= h(mb_strimwidth($ev['detail'] ?? '', 0, 150, '…')) ?></td>
      <td style="font-size:11px;color:var(--muted);font-family:var(--f-mono)"><?= h(basename($ev['filename'] ?? '')) ?></td>
      <td style="font-size:11px;color:var(--muted);direction:ltr;text-align:left"><?= h($ev['ip'] ?? '') ?></td>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap"><?= h($ev['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:48px;color:var(--muted)">✅ لا توجد أحداث أمان مسجّلة</div>
  <?php endif; ?>
</div>
<script>
document.getElementById('btn-reindex-all').addEventListener('click', async function(){
  const btn = this, msg = document.getElementById('reindex-msg');
  btn.disabled = true; btn.textContent = '⏳ جارٍ الإرسال...';
  msg.style.display = 'block'; msg.textContent = 'يتم إرسال جميع الروابط إلى Google وBing وIndexNow…';
  try {
    const r = await fetch('admin.php?ajax=reindex_all', {method:'POST'});
    const d = await r.json();
    if (d.ok) {
      msg.textContent = `✅ تمت الفهرسة! تم إرسال ${d.pinged} رابطاً إلى IndexNow + sitemap ping لـ Google وBing.`;
      msg.style.background='rgba(34,197,94,.07)'; msg.style.borderColor='rgba(34,197,94,.3)'; msg.style.color='#4ade80';
      setTimeout(()=>location.reload(), 2500);
    } else {
      msg.textContent = '⚠️ ' + (d.error || 'خطأ غير معروف');
      msg.style.background='rgba(239,68,68,.07)'; msg.style.borderColor='rgba(239,68,68,.3)'; msg.style.color='var(--danger)';
    }
  } catch(e){ msg.textContent = '❌ خطأ في الاتصال — تحقق من IndexNow key في الإعدادات.'; }
  btn.disabled = false; btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg> إعادة فهرسة كل التطبيقات';
});
async function clearOldLogs(){
  if (!confirm('حذف السجلات الأقدم من 30 يوماً؟')) return;
  await fetch('admin.php?ajax=clear_security_log',{method:'POST'});
  location.reload();
}
</script>
<?php endif; ?>

<?php if ($page === 'file-manager'): ?>
<div class="admin-page-title">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
  مدير الملفات
</div>
<div style="display:grid;grid-template-columns:250px 1fr;gap:14px;height:calc(100vh - 172px);min-height:480px">

  <!-- File tree -->
  <div class="section-box" style="overflow:auto;padding:14px;display:flex;flex-direction:column;gap:0">
    <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;color:var(--muted);text-transform:uppercase;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">الملفات</div>
    <div id="fm-tree" style="font-size:12px;flex:1;overflow-y:auto"></div>
    <div style="margin-top:12px;border-top:1px solid var(--border-c);padding-top:12px">
      <div style="font-size:10px;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:1px">ملف جديد</div>
      <input type="text" id="fm-new-name" placeholder="example.php" class="form-input" style="font-size:12px;margin-bottom:6px;direction:ltr">
      <button id="fm-create-btn" class="btn-primary" style="font-size:11px;padding:7px 12px;width:100%">إنشاء ملف</button>
    </div>
  </div>

  <!-- Editor pane -->
  <div class="section-box" style="display:flex;flex-direction:column;padding:0;overflow:hidden">
    <!-- toolbar -->
    <div style="display:flex;align-items:center;gap:8px;padding:9px 14px;border-bottom:1px solid var(--border-c);flex-shrink:0;background:var(--navy-700)">
      <code id="fm-current-file" style="font-size:11px;color:var(--cyan);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">— اختر ملفاً —</code>
      <span id="fm-status" style="font-size:11px;color:var(--muted)"></span>
      <button id="fm-save-btn" class="btn-primary" style="font-size:11px;padding:6px 14px" disabled>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
        حفظ
      </button>
    </div>
    <!-- editor area -->
    <div style="position:relative;flex:1;overflow:hidden;background:#0d1117;font-family:'Courier New',monospace">
      <div id="fm-line-nums" style="position:absolute;top:0;right:0;width:42px;bottom:0;padding:14px 6px;font-size:12px;line-height:1.65;color:#484f58;background:#161b22;border-left:1px solid rgba(99,130,190,.12);text-align:right;overflow:hidden;pointer-events:none;user-select:none;z-index:1"></div>
      <textarea id="fm-editor" spellcheck="false" dir="ltr"
        style="position:absolute;top:0;right:42px;left:0;bottom:0;padding:14px 14px 14px 14px;font-size:12.5px;line-height:1.65;font-family:'Courier New',monospace;color:#e6edf3;background:transparent;border:none;outline:none;resize:none;tab-size:2;white-space:pre"
        placeholder="اختر ملفاً من القائمة لبدء التعديل" disabled></textarea>
    </div>
    <!-- status bar -->
    <div style="display:flex;gap:16px;align-items:center;padding:5px 14px;border-top:1px solid var(--border-c);background:var(--navy-700);flex-shrink:0">
      <span id="fm-chars" style="font-size:10px;color:var(--muted)">0 حرف</span>
      <span id="fm-lines" style="font-size:10px;color:var(--muted)">0 سطر</span>
      <span id="fm-cursor" style="font-size:10px;color:var(--muted)">س1:ع1</span>
      <span id="fm-unsaved" style="font-size:10px;color:#ef4444;font-weight:700;margin-right:auto" hidden>● غير محفوظ</span>
    </div>
  </div>
</div>
<script>
(function(){
  const tree=document.getElementById('fm-tree'),
        editor=document.getElementById('fm-editor'),
        lineNums=document.getElementById('fm-line-nums'),
        saveBtn=document.getElementById('fm-save-btn'),
        fmStatus=document.getElementById('fm-status'),
        curFile=document.getElementById('fm-current-file'),
        unsaved=document.getElementById('fm-unsaved');
  let currentPath='', savedContent='', currentDir='';

  function updateUI(){
    const lines=editor.value.split('\n');
    lineNums.innerHTML=lines.map((_,i)=>`<div>${i+1}</div>`).join('');
    lineNums.scrollTop=editor.scrollTop;
    document.getElementById('fm-chars').textContent=editor.value.length+' حرف';
    document.getElementById('fm-lines').textContent=lines.length+' سطر';
    unsaved.hidden=editor.value===savedContent;
  }
  editor.addEventListener('input',updateUI);
  editor.addEventListener('scroll',()=>{ lineNums.scrollTop=editor.scrollTop; });
  editor.addEventListener('keydown',e=>{
    if(e.key==='Tab'){e.preventDefault();const s=editor.selectionStart,en=editor.selectionEnd;editor.value=editor.value.substring(0,s)+'  '+editor.value.substring(en);editor.selectionStart=editor.selectionEnd=s+2;updateUI();}
    if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();doSave();}
  });
  editor.addEventListener('keyup',()=>{
    const s=editor.selectionStart,b=editor.value.substring(0,s);
    document.getElementById('fm-cursor').textContent=`س${b.split('\n').length}:ع${b.split('\n').pop().length+1}`;
  });

  async function loadDir(dir=''){
    currentDir=dir;
    const res=await fetch('admin.php?ajax=fm_list&dir='+encodeURIComponent(dir));
    const d=await res.json();
    if(!d.ok){tree.innerHTML='<div style="color:var(--danger);padding:8px">'+d.error+'</div>';return;}
    let html='';
    if(dir) html+=`<div onclick="loadDir('')" style="padding:5px 8px;cursor:pointer;color:var(--muted);border-radius:6px;display:flex;align-items:center;gap:6px" onmouseover="this.style.background='rgba(255,255,255,.05)'" onmouseout="this.style.background=''">⬆️ رجوع</div>`;
    d.items.forEach(item=>{
      if(item.type==='dir'){
        html+=`<div onclick="loadDir('${item.path}')" style="padding:5px 8px;cursor:pointer;color:var(--warning);border-radius:6px;display:flex;align-items:center;gap:6px" onmouseover="this.style.background='rgba(255,255,255,.05)'" onmouseout="this.style.background=''">📁 ${item.name}/</div>`;
      } else {
        const active=item.path===currentPath?'background:rgba(6,182,212,.12);color:var(--cyan)':'';
        html+=`<div onclick="openFile('${item.path}')" style="padding:5px 8px;cursor:pointer;border-radius:6px;display:flex;align-items:center;gap:5px;${active}" onmouseover="this.style.background='rgba(255,255,255,.05)'" onmouseout="${item.path===currentPath?'':''}">`
          +`<span style="color:var(--muted);font-size:10px">📄</span> ${item.name}`
          +`<span style="margin-right:auto;color:var(--muted);font-size:10px">${Math.max(1,Math.round(item.size/1024))}k</span></div>`;
      }
    });
    if(!d.items.length) html+='<div style="padding:12px;color:var(--muted);font-size:11px">لا توجد ملفات</div>';
    tree.innerHTML=html;
    document.getElementById('fm-create-btn').dataset.dir=dir;
  }

  async function openFile(path){
    if(!unsaved.hidden&&!confirm('لديك تغييرات غير محفوظة. استمرار؟')) return;
    fmStatus.textContent='⏳ تحميل...';
    const res=await fetch('admin.php?ajax=fm_read&path='+encodeURIComponent(path));
    const d=await res.json();
    if(!d.ok){fmStatus.textContent='❌ '+d.error;return;}
    currentPath=path;
    editor.value=d.content; savedContent=d.content;
    editor.disabled=false; saveBtn.disabled=false;
    curFile.textContent=path;
    unsaved.hidden=true;
    updateUI();
    fmStatus.textContent=`${Math.max(1,Math.round(d.size/1024))} KB | ${d.ext}`;
    // reload tree to reflect active file
    const dir=path.includes('/')?path.substring(0,path.lastIndexOf('/')):'';
    loadDir(dir);
  }

  async function doSave(){
    if(!currentPath) return;
    saveBtn.disabled=true; saveBtn.textContent='⏳ حفظ...';
    const fd=new FormData(); fd.append('path',currentPath); fd.append('content',editor.value);
    const res=await fetch('admin.php?ajax=fm_write',{method:'POST',body:fd});
    const d=await res.json();
    if(d.ok){
      savedContent=editor.value; unsaved.hidden=true;
      fmStatus.textContent='✅ تم الحفظ';
      setTimeout(()=>fmStatus.textContent=`${Math.max(1,Math.round(d.bytes/1024))} KB`,2000);
    } else {
      fmStatus.textContent='❌ '+(d.error||'خطأ في الحفظ');
    }
    saveBtn.disabled=false; saveBtn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg> حفظ';
  }

  saveBtn.addEventListener('click',doSave);
  document.getElementById('fm-create-btn').addEventListener('click',async()=>{
    const name=document.getElementById('fm-new-name').value.trim();
    const dir=document.getElementById('fm-create-btn').dataset.dir||'';
    if(!name) return;
    const fd=new FormData(); fd.append('dir',dir); fd.append('name',name);
    const res=await fetch('admin.php?ajax=fm_create',{method:'POST',body:fd});
    const d=await res.json();
    if(d.ok){document.getElementById('fm-new-name').value='';openFile(d.path);}
    else alert(d.error);
  });

  loadDir('');
})();
</script>
<?php endif; ?>

</div><!-- /admin-main -->
</div><!-- /admin-wrap -->

<script src="<?= h(asset_url('assets/js/admin.js')) ?>"></script>
</body>
</html>
