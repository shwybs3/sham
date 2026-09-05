#!/usr/bin/env node
'use strict';
/**
 * نشر موقع من مجلد محلي أو مستودع Git إلى الاستضافة.
 * الاستخدام:
 *   shamhost deploy <domain> <path|git-url> [--type php|static|proxy] [--port 3000] [--db] [--force]
 */
const fs = require('fs');
const path = require('path');
const { P } = require('./lib/config');
const { run, isDomain } = require('./lib/util');
const sites = require('./lib/sites');
const db = require('./lib/db');
const crypto = require('crypto');

const argv = process.argv.slice(2);
const domain = (argv[0] || '').toLowerCase();
const source = argv[1] || '';
const flag = (name, def) => {
  const i = argv.indexOf('--' + name);
  if (i < 0) return def;
  const next = argv[i + 1];
  return (next && !next.startsWith('--')) ? next : true;
};

function usage(msg) {
  if (msg) console.error('\nخطأ: ' + msg);
  console.error(`
الاستخدام:
  shamhost deploy <domain> <مسار-محلي أو رابط-git> [خيارات]

الخيارات:
  --type php|static|proxy   نوع الموقع (افتراضي: php إن وُجد ملف .php، وإلا static)
  --port <رقم>              منفذ التطبيق لنوع proxy
  --db                      إنشاء قاعدة بيانات ومستخدم للموقع
  --force                   الكتابة فوق موقع موجود بنفس الدومين

أمثلة:
  shamhost deploy blog.example.com ~/downloads/mysite --db
  shamhost deploy example.com https://github.com/user/repo.git --type php --db
`);
  process.exit(msg ? 1 : 0);
}

function detectType(dir) {
  try {
    const entries = fs.readdirSync(dir);
    if (entries.some(f => f.toLowerCase().endsWith('.php'))) return 'php';
    if (entries.includes('package.json') && !entries.includes('index.html')) return 'proxy';
  } catch (_) {}
  return 'static';
}

async function copyTree(src, dest) {
  fs.mkdirSync(dest, { recursive: true });
  const r = await run('sh', ['-c', 'cp -a "' + src.replace(/"/g, '\\"') + '/." "' + dest.replace(/"/g, '\\"') + '/"'], { timeout: 900000 });
  if (!r.ok) throw new Error('فشل نسخ الملفات: ' + (r.stderr || '').slice(-400));
}

(async () => {
  if (!domain || !source || argv.includes('--help') || argv.includes('-h')) usage();
  if (!isDomain(domain)) usage('اسم دومين غير صالح: ' + domain);

  const existing = sites.byDomain(domain);
  if (existing && !flag('force')) usage('الدومين مضاف مسبقاً. استخدم --force للكتابة فوقه.');

  // 1) تجهيز المصدر
  const staging = path.join(P.TMP, 'deploy-' + Date.now());
  console.log('▶ تجهيز المصدر …');
  if (/^(https?|git|ssh):\/\/|^git@/.test(source)) {
    const r = await run('git', ['clone', '--depth', '1', source, staging], { timeout: 900000 });
    if (!r.ok) { console.error('فشل الاستنساخ:\n' + (r.stderr || '').slice(-800)); process.exit(1); }
    try { fs.rmSync(path.join(staging, '.git'), { recursive: true, force: true }); } catch (_) {}
    console.log('  ✓ استُنسخ المستودع');
  } else {
    const abs = path.resolve(source);
    if (!fs.existsSync(abs)) { console.error('المسار غير موجود: ' + abs); process.exit(1); }
    await copyTree(abs, staging);
    console.log('  ✓ نُسخت الملفات');
  }

  const type = flag('type') || detectType(staging);
  console.log('  • النوع: ' + type);

  // 2) إنشاء أو تحديث الموقع
  let site = existing;
  if (!site) {
    console.log('▶ إنشاء الموقع …');
    const r = await sites.create({ domain, type, proxy_port: flag('port', 3000) });
    if (!r.ok) { console.error('فشل: ' + r.error); process.exit(1); }
    site = r.site;
    console.log('  ✓ ' + site.root);
  } else {
    console.log('▶ تحديث موقع قائم: ' + site.root);
  }

  // 3) نقل الملفات
  console.log('▶ نشر الملفات …');
  try { fs.rmSync(site.root, { recursive: true, force: true }); } catch (_) {}
  fs.mkdirSync(path.dirname(site.root), { recursive: true });
  await copyTree(staging, site.root);
  try { fs.rmSync(staging, { recursive: true, force: true }); } catch (_) {}
  console.log('  ✓ نُشرت الملفات');

  // 4) قاعدة البيانات
  if (flag('db')) {
    console.log('▶ تجهيز قاعدة البيانات …');
    const pass = crypto.randomBytes(9).toString('base64url');
    const d = await db.provision(site.id.replace(/[^a-z0-9]/g, ''), pass);
    if (d.ok) {
      const cfgMod = require('./lib/config');
      cfgMod.update(c => {
        const s = c.sites.find(x => x.id === site.id);
        if (s) s.db = { database: d.database, user: d.user };
      });
      console.log('  ✓ القاعدة:      ' + d.database);
      console.log('    المستخدم:     ' + d.user);
      console.log('    كلمة المرور:  ' + pass);
      console.log('    المضيف:       127.0.0.1:3306');
      console.log('    ⚠ انسخها الآن، لن تُعرض مرة أخرى.');
    } else {
      console.log('  ! تعذّر إنشاء قاعدة البيانات: ' + d.error);
    }
  }

  const nginx = require('./lib/nginx');
  const rl = await nginx.reload();
  console.log(rl.ok ? '▶ أُعيد تحميل nginx' : '! تحذير: ' + (rl.error || rl.output));

  const cfg = require('./lib/config').load();
  console.log('\n🎉 تم النشر.');
  console.log('   محلياً:  http://' + require('./lib/system').lanIp() + ':' + cfg.web.http_port + '  (بترويسة Host: ' + domain + ')');
  console.log('   للنشر على الإنترنت افتح اللوحة ← الأنفاق، أو اربط الدومين من صفحة الدومينات.');
})().catch(e => { console.error('خطأ: ' + e.message); process.exit(1); });
