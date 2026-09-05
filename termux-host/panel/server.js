'use strict';
/**
 * ShamHost — لوحة تحكم الاستضافة على أندرويد.
 * خادم HTTP بلا أي اعتماديات خارجية (Node stdlib فقط) ليعمل على أي معمارية في Termux.
 */
const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');
const crypto = require('crypto');

const { P, load, update } = require('./lib/config');
const util = require('./lib/util');
const auth = require('./lib/auth');
const sites = require('./lib/sites');
const nginx = require('./lib/nginx');
const db = require('./lib/db');
const namecheap = require('./lib/namecheap');
const tunnel = require('./lib/tunnel');
const ssl = require('./lib/ssl');
const files = require('./lib/files');
const cron = require('./lib/cron');
const backup = require('./lib/backup');
const system = require('./lib/system');

const PUBLIC = path.join(__dirname, 'public');
const MAX_JSON = 12 * 1024 * 1024;
const MAX_UPLOAD = 96 * 1024 * 1024;

// ───────────────────────────── أدوات مساعدة ─────────────────────────────

function json(res, code, obj) {
  const body = JSON.stringify(obj);
  res.writeHead(code, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(body),
    'Cache-Control': 'no-store',
    'X-Content-Type-Options': 'nosniff',
  });
  res.end(body);
}

function cookies(req) {
  const out = {};
  const raw = req.headers.cookie;
  if (!raw) return out;
  for (const part of raw.split(';')) {
    const i = part.indexOf('=');
    if (i > 0) out[part.slice(0, i).trim()] = decodeURIComponent(part.slice(i + 1).trim());
  }
  return out;
}

function clientIp(req) {
  return (req.socket.remoteAddress || '').replace(/^::ffff:/, '') || 'unknown';
}

function readBody(req, limit) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    let size = 0;
    req.on('data', (c) => {
      size += c.length;
      if (size > limit) { reject(Object.assign(new Error('الطلب أكبر من الحد المسموح'), { code: 'E2BIG' })); req.destroy(); return; }
      chunks.push(c);
    });
    req.on('end', () => resolve(Buffer.concat(chunks)));
    req.on('error', reject);
  });
}

/** محلّل multipart/form-data مبسّط — ملف واحد + حقول نصية */
function parseMultipart(buf, boundary) {
  const delim = Buffer.from('--' + boundary);
  const parts = [];
  let start = buf.indexOf(delim);
  if (start < 0) return parts;
  start += delim.length;
  while (start < buf.length) {
    if (buf[start] === 0x2d && buf[start + 1] === 0x2d) break;      // "--" نهاية
    while (start < buf.length && (buf[start] === 0x0d || buf[start] === 0x0a)) start++;
    const headEnd = buf.indexOf('\r\n\r\n', start);
    if (headEnd < 0) break;
    const head = buf.slice(start, headEnd).toString('utf8');
    let next = buf.indexOf(delim, headEnd);
    if (next < 0) next = buf.length;
    let content = buf.slice(headEnd + 4, next);
    if (content.length >= 2 && content[content.length - 2] === 0x0d) content = content.slice(0, -2);
    const nameM = /name="([^"]*)"/i.exec(head);
    const fileM = /filename="([^"]*)"/i.exec(head);
    parts.push({
      name: nameM ? nameM[1] : '',
      filename: fileM ? fileM[1] : null,
      content,
    });
    start = next + delim.length;
  }
  return parts;
}

const MIME = {
  '.html': 'text/html; charset=utf-8', '.js': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8', '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg',
  '.ico': 'image/x-icon', '.woff2': 'font/woff2', '.map': 'application/json',
};

function serveStatic(req, res, pathname) {
  let rel = pathname === '/' ? 'index.html' : pathname.replace(/^\/+/, '');
  let file;
  try { file = util.safeJoin(PUBLIC, rel); } catch (_) { return json(res, 400, { error: 'مسار غير صالح' }); }
  if (!fs.existsSync(file) || !fs.statSync(file).isFile()) {
    file = path.join(PUBLIC, 'index.html');
    if (!fs.existsSync(file)) return json(res, 404, { error: 'غير موجود' });
  }
  const ext = path.extname(file).toLowerCase();
  const body = fs.readFileSync(file);
  res.writeHead(200, {
    'Content-Type': MIME[ext] || 'application/octet-stream',
    'Content-Length': body.length,
    'Cache-Control': ext === '.html' ? 'no-store' : 'max-age=300',
    'X-Frame-Options': 'SAMEORIGIN',
    'X-Content-Type-Options': 'nosniff',
    'Referrer-Policy': 'same-origin',
  });
  res.end(body);
}

// ───────────────────────────── جدول المسارات ─────────────────────────────

const routes = [];
function route(method, pattern, handler, opts = {}) {
  const keys = [];
  const rx = new RegExp('^' + pattern.replace(/:[a-zA-Z_]+/g, (m) => { keys.push(m.slice(1)); return '([^/]+)'; }) + '$');
  routes.push({ method, rx, keys, handler, open: !!opts.open, raw: !!opts.raw });
}

const ok = (data) => Object.assign({ ok: true }, data || {});
const fail = (error, code) => ({ ok: false, error, __code: code || 400 });

// ── المصادقة ──
route('POST', '/api/login', async (c) => {
  const r = auth.login(c.body.username, c.body.password, c.ip);
  if (!r.ok) return fail(r.error, 401);
  c.res.setHeader('Set-Cookie',
    'sh_session=' + r.token + '; HttpOnly; Path=/; SameSite=Strict; Max-Age=' + (12 * 3600));
  return ok({ user: r.user });
}, { open: true });

route('POST', '/api/logout', async (c) => {
  auth.logout(c.token);
  c.res.setHeader('Set-Cookie', 'sh_session=; HttpOnly; Path=/; SameSite=Strict; Max-Age=0');
  return ok();
});

route('GET', '/api/me', async (c) => ok({ user: c.session.user, since: c.session.created }));

// ── النظام ──
route('GET', '/api/system/summary', async () => ok({ summary: await system.summary() }));
route('GET', '/api/system/services', async () => ok({ services: system.services() }));
route('POST', '/api/system/service', async (c) => {
  const r = await system.control(c.body.name, c.body.action);
  return r.ok ? ok({ output: r.output }) : fail(r.error || r.output);
});
route('GET', '/api/system/logs', async (c) => {
  const r = system.readLog(c.query.name || 'panel', c.query.lines);
  return r.ok ? ok({ content: r.content }) : fail(r.error);
});
route('GET', '/api/system/log-names', async () => ok({ names: await system.logsList() }));
route('POST', '/api/system/exec', async (c) => ok(await system.exec(c.body.command, c.body.cwd)));
route('GET', '/api/system/publicip', async () => ok({ ip: await namecheap.publicIp() }));

// ── المواقع ──
route('GET', '/api/sites', async () => {
  const list = sites.list().map(s => Object.assign({}, s, { size: sites.sizeOf(s.root) }));
  return ok({ sites: list });
});
route('POST', '/api/sites', async (c) => {
  const r = await sites.create(c.body);
  if (!r.ok) return fail(r.error);
  if (c.body.create_db) {
    const pass = crypto.randomBytes(9).toString('base64url');
    const d = await db.provision(r.site.id.replace(/[^a-z0-9]/g, ''), pass);
    if (d.ok) {
      update(cfg => {
        const s = cfg.sites.find(x => x.id === r.site.id);
        if (s) s.db = { database: d.database, user: d.user };
      });
      r.site.db = { database: d.database, user: d.user };
      r.dbCredentials = { database: d.database, user: d.user, password: pass, host: '127.0.0.1', port: 3306 };
    } else {
      r.dbWarning = d.error;
    }
  }
  return ok({ site: r.site, dbCredentials: r.dbCredentials || null, dbWarning: r.dbWarning || null });
});
route('GET', '/api/sites/:id', async (c) => {
  const s = sites.get(c.params.id);
  return s ? ok({ site: s, size: sites.sizeOf(s.root) }) : fail('الموقع غير موجود', 404);
});
route('POST', '/api/sites/:id', async (c) => {
  const r = await sites.edit(c.params.id, c.body);
  return r.ok ? ok({ site: r.site }) : fail(r.error);
});
route('DELETE', '/api/sites/:id', async (c) => {
  const r = await sites.remove(c.params.id, c.query.files === '1');
  return r.ok ? ok() : fail(r.error);
});
route('GET', '/api/sites/:id/logs', async (c) => {
  const r = system.siteLog(c.params.id, c.query.kind, c.query.lines);
  return r.ok ? ok({ content: r.content }) : fail(r.error);
});
route('POST', '/api/nginx/reload', async () => {
  const r = await nginx.reload();
  return r.ok ? ok({ output: r.output }) : fail(r.error + '\n' + (r.output || ''));
});
route('POST', '/api/nginx/rebuild', async () => {
  const r = await nginx.rebuildAll();
  return r.ok ? ok({ output: r.output }) : fail((r.error || '') + '\n' + (r.output || ''));
});
route('GET', '/api/nginx/test', async () => {
  const r = await nginx.test();
  return ok({ valid: r.ok, output: r.output });
});

// ── قواعد البيانات ──
route('GET', '/api/db/status', async () => ok({ status: await db.ping() }));
route('GET', '/api/db/databases', async () => {
  const r = await db.listDatabases();
  return r.ok ? ok({ databases: r.databases }) : fail(r.error);
});
route('POST', '/api/db/databases', async (c) => {
  const r = await db.createDatabase(c.body.name);
  return r.ok ? ok({ name: r.name }) : fail(r.error);
});
route('DELETE', '/api/db/databases/:name', async (c) => {
  const r = await db.dropDatabase(c.params.name);
  return r.ok ? ok() : fail(r.error);
});
route('GET', '/api/db/users', async () => {
  const r = await db.listUsers();
  return r.ok ? ok({ users: r.users }) : fail(r.error);
});
route('POST', '/api/db/users', async (c) => {
  const r = await db.createUser(c.body.name, c.body.password);
  return r.ok ? ok({ user: r.user }) : fail(r.error);
});
route('DELETE', '/api/db/users/:name', async (c) => {
  const r = await db.dropUser(c.params.name);
  return r.ok ? ok() : fail(r.error);
});
route('POST', '/api/db/grant', async (c) => {
  const r = await db.grant(c.body.user, c.body.database, c.body.privileges);
  return r.ok ? ok() : fail(r.error);
});
route('POST', '/api/db/query', async (c) => {
  const r = await db.query(String(c.body.sql || ''), { db: c.body.database });
  return r.ok ? ok({ columns: r.columns, rows: r.rows }) : fail(r.error);
});
route('POST', '/api/db/provision', async (c) => {
  const pass = c.body.password || crypto.randomBytes(9).toString('base64url');
  const r = await db.provision(c.body.base || 'site', pass);
  return r.ok ? ok({ database: r.database, user: r.user, password: pass }) : fail(r.error);
});

// ── الدومينات و DNS ──
route('GET', '/api/dns/status', async () => ok({ configured: namecheap.configured(), ip: (load().namecheap || {}).client_ip || null }));
route('GET', '/api/dns/domains', async () => {
  const r = await namecheap.getDomains();
  return r.ok ? ok({ domains: r.domains }) : fail(r.error);
});
route('GET', '/api/dns/hosts', async (c) => {
  const r = await namecheap.getHosts(c.query.domain);
  return r.ok ? ok({ hosts: r.hosts }) : fail(r.error);
});
route('POST', '/api/dns/hosts', async (c) => {
  const r = await namecheap.applyRecords(c.body.domain, c.body.upserts || [], c.body.removals || []);
  return r.ok ? ok({ hosts: r.hosts }) : fail(r.error);
});
route('POST', '/api/dns/point', async (c) => {
  const r = await namecheap.pointTo(c.body.domain, c.body.target, {
    www: c.body.www !== false,
    subdomains: c.body.subdomains || [],
    ttl: c.body.ttl,
  });
  return r.ok ? ok({ applied: r.applied, type: r.type }) : fail(r.error);
});
route('GET', '/api/dns/nameservers', async (c) => {
  const r = await namecheap.getNameservers(c.query.domain);
  return r.ok ? ok(r) : fail(r.error);
});
route('POST', '/api/dns/nameservers', async (c) => {
  const r = c.body.reset
    ? await namecheap.setDefaultNameservers(c.body.domain)
    : await namecheap.setCustomNameservers(c.body.domain, c.body.nameservers || []);
  return r.ok ? ok() : fail(r.error);
});
route('GET', '/api/dns/ddns', async () => ok({ entries: (load().ddns || []).map(e => Object.assign({}, e, { password: e.password ? '••••••' : '' })) }));
route('POST', '/api/dns/ddns', async (c) => {
  const { host, domain, password } = c.body;
  if (!util.isDomain(domain)) return fail('دومين غير صالح');
  if (!password) return fail('كلمة مرور DDNS مطلوبة (من لوحة Namecheap: Advanced DNS)');
  const ip = c.body.ip || await namecheap.publicIp();
  const r = await namecheap.ddnsUpdate(host || '@', domain, password, ip);
  if (!r.ok) return fail(r.error);
  update(cfg => {
    cfg.ddns = (cfg.ddns || []).filter(e => !(e.host === (host || '@') && e.domain === domain));
    cfg.ddns.push({ host: host || '@', domain, password, last_ip: r.ip, updated_at: new Date().toISOString() });
  });
  return ok({ ip: r.ip });
});
route('POST', '/api/dns/ddns/sync', async () => {
  const cfg = load();
  const ip = await namecheap.publicIp();
  if (!ip) return fail('تعذّر تحديد عنوان IP الحالي');
  const results = [];
  for (const e of cfg.ddns || []) {
    const r = await namecheap.ddnsUpdate(e.host, e.domain, e.password, ip);
    results.push({ host: e.host, domain: e.domain, ok: r.ok, error: r.error || null });
  }
  update(c2 => { (c2.ddns || []).forEach(e => { e.last_ip = ip; e.updated_at = new Date().toISOString(); }); });
  return ok({ ip, results });
});
route('DELETE', '/api/dns/ddns/:key', async (c) => {
  const key = decodeURIComponent(c.params.key);
  update(cfg => { cfg.ddns = (cfg.ddns || []).filter(e => (e.host + '.' + e.domain) !== key); });
  return ok();
});

// ── الأنفاق ──
route('GET', '/api/tunnels', async () => ok({ tunnels: tunnel.list(), installed: !!(await tunnel.installed()) }));
route('POST', '/api/tunnels', async (c) => {
  const r = await tunnel.start(c.body);
  return r.ok ? ok({ tunnel: r.tunnel }) : fail(r.error);
});
route('DELETE', '/api/tunnels/:id', async (c) => {
  const r = tunnel.stop(c.params.id);
  return r.ok ? ok() : fail(r.error);
});
route('GET', '/api/tunnels/:id/logs', async (c) => ok({ content: tunnel.logs(c.params.id, c.query.lines) }));

// ── SSL ──
route('GET', '/api/ssl', async () => {
  const list = ssl.list();
  for (const c of list) c.expires = await ssl.expiry(c.domain);
  return ok({ certificates: list, acme: ssl.acmeInstalled() });
});
route('POST', '/api/ssl/issue', async (c) => {
  const domain = String(c.body.domain || '');
  const r = c.body.method === 'self-signed'
    ? await ssl.issueSelfSigned(domain)
    : await ssl.issueLetsEncrypt(domain, { wildcard: !!c.body.wildcard, www: c.body.www !== false, force: !!c.body.force });
  if (!r.ok) return fail(r.error + (r.log ? '\n\n' + r.log : ''));
  sites.setSsl(domain, true);
  const site = sites.byDomain(domain);
  if (site) { nginx.writeVhost(sites.get(site.id)); await nginx.reload(); }
  return ok({ domain, selfSigned: !!r.selfSigned });
});
route('POST', '/api/ssl/renew', async () => ok(await ssl.renewAll()));
route('DELETE', '/api/ssl/:domain', async (c) => {
  const domain = decodeURIComponent(c.params.domain);
  ssl.remove(domain);
  sites.setSsl(domain, false);
  const site = sites.byDomain(domain);
  if (site) { nginx.writeVhost(sites.get(site.id)); await nginx.reload(); }
  return ok();
});

// ── مدير الملفات ──
route('GET', '/api/files/roots', async () => ok({
  roots: Object.keys(files.ROOTS).map(k => ({ key: k, label: files.ROOTS[k].label, dir: files.ROOTS[k].dir })),
}));
route('GET', '/api/files/list', async (c) => ok({ listing: files.list(c.query.root || 'sites', c.query.path || '') }));
route('GET', '/api/files/read', async (c) => ok(files.read(c.query.root || 'sites', c.query.path || '')));
route('POST', '/api/files/write', async (c) => ok(files.write(c.body.root, c.body.path, c.body.content)));
route('POST', '/api/files/mkdir', async (c) => ok(files.mkdir(c.body.root, c.body.path)));
route('POST', '/api/files/rename', async (c) => ok(files.rename(c.body.root, c.body.path, c.body.to)));
route('POST', '/api/files/chmod', async (c) => files.chmod(c.body.root, c.body.path, c.body.mode));
route('POST', '/api/files/extract', async (c) => await files.extract(c.body.root, c.body.path));
route('POST', '/api/files/compress', async (c) => await files.compress(c.body.root, c.body.path, c.body.name));
route('DELETE', '/api/files', async (c) => files.remove(c.query.root, c.query.path));

// ── المهام المجدولة ──
route('GET', '/api/cron', async () => ok({ jobs: await cron.list(), raw: await cron.raw() }));
route('POST', '/api/cron', async (c) => {
  const r = await cron.add(c.body.schedule, c.body.command, c.body.comment);
  return r.ok ? ok() : fail(r.error);
});
route('DELETE', '/api/cron/:id', async (c) => {
  const r = await cron.remove(c.params.id);
  return r.ok ? ok() : fail(r.error);
});

// ── النسخ الاحتياطي ──
route('GET', '/api/backups', async () => ok({ backups: backup.list() }));
route('POST', '/api/backups', async (c) => {
  const r = await backup.create(c.body.site, { database: c.body.database });
  return r.ok ? ok({ name: r.name, sizeText: r.sizeText }) : fail(r.error);
});
route('POST', '/api/backups/restore', async (c) => {
  const r = await backup.restore(c.body.name, { database: c.body.database });
  return r.ok ? ok(r) : fail(r.error);
});
route('DELETE', '/api/backups/:name', async (c) => {
  const r = backup.remove(decodeURIComponent(c.params.name));
  return r.ok ? ok() : fail(r.error);
});
route('POST', '/api/backups/prune', async (c) => ok(backup.prune(c.body.keep)));

// ── الإعدادات ──
route('GET', '/api/settings', async () => {
  const cfg = load();
  return ok({
    settings: {
      panel: cfg.panel,
      web: cfg.web,
      db: { admin_user: cfg.db.admin_user, host: cfg.db.host, port: cfg.db.port, has_password: !!cfg.db.admin_pass },
      namecheap: {
        api_user: cfg.namecheap.api_user,
        username: cfg.namecheap.username,
        client_ip: cfg.namecheap.client_ip,
        sandbox: !!cfg.namecheap.sandbox,
        has_key: !!cfg.namecheap.api_key,
      },
      api_token_set: !!cfg.api_token,
      version: cfg.version,
    },
  });
});
route('POST', '/api/settings', async (c) => {
  const b = c.body || {};
  update(cfg => {
    if (b.namecheap) {
      const n = b.namecheap;
      if (n.api_user !== undefined) cfg.namecheap.api_user = String(n.api_user).trim();
      if (n.username !== undefined) cfg.namecheap.username = String(n.username).trim();
      if (n.api_key) cfg.namecheap.api_key = String(n.api_key).trim();
      if (n.client_ip !== undefined) cfg.namecheap.client_ip = String(n.client_ip).trim();
      if (n.sandbox !== undefined) cfg.namecheap.sandbox = !!n.sandbox;
    }
    if (b.db) {
      if (b.db.admin_user) cfg.db.admin_user = String(b.db.admin_user).trim();
      if (b.db.admin_pass !== undefined) cfg.db.admin_pass = String(b.db.admin_pass);
      if (b.db.host) cfg.db.host = String(b.db.host).trim();
      if (b.db.port) cfg.db.port = parseInt(b.db.port, 10) || 3306;
    }
    if (b.panel && b.panel.port) cfg.panel.port = parseInt(b.panel.port, 10) || 8088;
    if (b.panel && b.panel.bind) cfg.panel.bind = String(b.panel.bind).trim();
  });
  return ok();
});
route('POST', '/api/settings/password', async (c) => {
  const r = auth.setPassword(c.body.password);
  return r.ok ? ok() : fail(r.error);
});
route('GET', '/api/settings/api-token', async () => ok({ token: load().api_token, username: load().admin.username }));
route('POST', '/api/settings/api-token', async () => {
  const token = crypto.randomBytes(24).toString('hex');
  update(cfg => { cfg.api_token = token; });
  return ok({ token });
});

// ───────────── واجهة متوافقة مع cPanel UAPI ─────────────
// تسمح لأي تطبيق يتحدث بلغة cPanel (مثل CPanelClient في هذا المستودع)
// بإنشاء نطاقات فرعية وقواعد بيانات على هذا الجهاز دون تعديل كوده.
async function uapi(module, fn, params) {
  const key = module + '.' + fn;
  switch (key) {
    case 'SubDomain.addsubdomain': {
      const label = String(params.domain || '').trim().toLowerCase();
      const rootDomain = String(params.rootdomain || '').trim().toLowerCase();
      if (!label || !util.isDomain(rootDomain)) return { ok: false, error: 'domain و rootdomain مطلوبان' };
      const full = label + '.' + rootDomain;
      const dir = String(params.dir || '').replace(/^\/+/, '');
      const parent = sites.byDomain(rootDomain);
      const root = dir && parent
        ? path.join(path.dirname(parent.root), dir.replace(/^public_html\/?/, parent.id + '/'))
        : undefined;
      const r = await sites.create({ domain: full, type: (parent && parent.type) || 'php', root });
      if (!r.ok) return { ok: false, error: r.error };
      return { ok: true, data: { domain: full, dir: r.site.root, id: r.site.id } };
    }
    case 'SubDomain.delsubdomain': {
      const full = String(params.domain || '').trim().toLowerCase();
      const site = sites.byDomain(full);
      if (!site) return { ok: false, error: 'النطاق الفرعي غير موجود' };
      const r = await sites.remove(site.id, false);
      return r.ok ? { ok: true, data: {} } : { ok: false, error: r.error };
    }
    case 'SubDomain.list_subdomains': {
      const all = sites.list();
      return { ok: true, data: all.map(s => ({ domain: s.domain, dir: s.root, reldir: s.root })) };
    }
    case 'DomainInfo.list_domains': {
      const all = sites.list();
      return { ok: true, data: { main_domain: all[0] ? all[0].domain : null, sub_domains: all.map(s => s.domain), addon_domains: [], parked_domains: [] } };
    }
    case 'Mysql.create_database': {
      const r = await db.createDatabase(String(params.name || ''));
      return r.ok ? { ok: true, data: { name: r.name } } : { ok: false, error: r.error };
    }
    case 'Mysql.create_user': {
      const r = await db.createUser(String(params.name || ''), String(params.password || ''));
      return r.ok ? { ok: true, data: { name: r.user } } : { ok: false, error: r.error };
    }
    case 'Mysql.set_privileges_on_user': {
      const r = await db.grant(String(params.user || ''), String(params.database || ''), String(params.privileges || 'ALL PRIVILEGES'));
      return r.ok ? { ok: true, data: {} } : { ok: false, error: r.error };
    }
    case 'Mysql.list_databases': {
      const r = await db.listDatabases();
      return r.ok ? { ok: true, data: r.databases.map(d => ({ database: d.name, disk_usage: d.bytes })) } : { ok: false, error: r.error };
    }
    case 'Mysql.list_users': {
      const r = await db.listUsers();
      return r.ok ? { ok: true, data: r.users.map(u => ({ user: u.user })) } : { ok: false, error: r.error };
    }
    case 'Mysql.delete_database': {
      const r = await db.dropDatabase(String(params.name || ''));
      return r.ok ? { ok: true, data: {} } : { ok: false, error: r.error };
    }
    case 'StatsBar.get_stats': {
      const s = await system.summary();
      return { ok: true, data: [{ name: 'disk', value: s.disk ? s.disk.usedText : '?' }, { name: 'sites', value: s.counts.sites }] };
    }
    default:
      return { ok: false, error: 'الدالة غير مدعومة في هذه الواجهة: ' + key };
  }
}

function uapiResponse(res, r) {
  const body = r.ok
    ? { status: 1, errors: null, messages: null, data: r.data, metadata: { transformed: 1 } }
    : { status: 0, errors: [r.error], messages: null, data: null, metadata: { transformed: 1 } };
  json(res, 200, body);
}

// ───────────────────────────── الخادم ─────────────────────────────

const server = http.createServer(async (req, res) => {
  const parsed = url.parse(req.url, true);
  const pathname = decodeURIComponent(parsed.pathname);
  const ip = clientIp(req);

  try {
    // واجهة cPanel المتوافقة
    const um = /^\/execute\/([A-Za-z]+)\/([A-Za-z_]+)$/.exec(pathname);
    if (um) {
      const h = String(req.headers.authorization || '');
      const m = /^cpanel\s+([^:]+):(\S+)$/i.exec(h.trim());
      if (!m || !auth.verifyApiToken(m[1], m[2])) {
        return uapiResponse(res, { ok: false, error: 'Access denied: invalid API token' });
      }
      let params = parsed.query;
      if (req.method === 'POST') {
        const raw = (await readBody(req, MAX_JSON)).toString('utf8');
        const ct = String(req.headers['content-type'] || '');
        if (ct.includes('json')) { try { params = Object.assign({}, params, JSON.parse(raw)); } catch (_) {} }
        else params = Object.assign({}, params, Object.fromEntries(new URLSearchParams(raw)));
      }
      return uapiResponse(res, await uapi(um[1], um[2], params));
    }

    if (!pathname.startsWith('/api/')) {
      if (req.method !== 'GET') return json(res, 405, { error: 'طريقة غير مسموحة' });
      return serveStatic(req, res, pathname);
    }

    // تنزيل ملف (يحتاج جلسة، يُقدَّم كتدفق)
    if (pathname === '/api/files/download' && req.method === 'GET') {
      const session = auth.verify(cookies(req).sh_session);
      if (!session) return json(res, 401, { error: 'يلزم تسجيل الدخول' });
      let abs;
      try { abs = files.resolve(parsed.query.root || 'sites', parsed.query.path || '').abs; }
      catch (e) { return json(res, 400, { error: e.message }); }
      if (!fs.existsSync(abs) || !fs.statSync(abs).isFile()) return json(res, 404, { error: 'الملف غير موجود' });
      res.writeHead(200, {
        'Content-Type': 'application/octet-stream',
        'Content-Length': fs.statSync(abs).size,
        'Content-Disposition': 'attachment; filename="' + encodeURIComponent(path.basename(abs)) + '"',
      });
      return fs.createReadStream(abs).pipe(res);
    }

    // رفع ملف
    if (pathname === '/api/files/upload' && req.method === 'POST') {
      const session = auth.verify(cookies(req).sh_session);
      if (!session) return json(res, 401, { error: 'يلزم تسجيل الدخول' });
      if (String(req.headers['x-shamhost'] || '') !== '1') return json(res, 403, { error: 'ترويسة الحماية مفقودة' });
      const ct = String(req.headers['content-type'] || '');
      const bm = /boundary=(?:"([^"]+)"|([^;]+))/i.exec(ct);
      if (!bm) return json(res, 400, { error: 'صيغة رفع غير صالحة' });
      const buf = await readBody(req, MAX_UPLOAD);
      const parts = parseMultipart(buf, (bm[1] || bm[2]).trim());
      const fields = {};
      let saved = null;
      for (const p of parts) {
        if (p.filename) {
          saved = files.saveUpload(fields.root || 'sites', fields.path || '', p.filename, p.content);
        } else {
          fields[p.name] = p.content.toString('utf8');
        }
      }
      if (!saved) return json(res, 400, { error: 'لم يُرسل أي ملف' });
      return json(res, 200, { ok: true, file: saved });
    }

    // مطابقة المسار
    const hit = routes.find(r => r.method === req.method && r.rx.test(pathname));
    if (!hit) return json(res, 404, { error: 'مسار غير معروف: ' + pathname });

    const token = cookies(req).sh_session;
    const session = auth.verify(token);
    if (!hit.open && !session) return json(res, 401, { error: 'يلزم تسجيل الدخول' });

    // حماية CSRF: كل طلب مُعدِّل يجب أن يحمل هذه الترويسة
    if (req.method !== 'GET' && String(req.headers['x-shamhost'] || '') !== '1') {
      return json(res, 403, { error: 'ترويسة الحماية مفقودة' });
    }

    let body = {};
    if (req.method !== 'GET') {
      const raw = (await readBody(req, MAX_JSON)).toString('utf8');
      if (raw) { try { body = JSON.parse(raw); } catch (_) { return json(res, 400, { error: 'صيغة JSON غير صالحة' }); } }
    }

    const m2 = hit.rx.exec(pathname);
    const params = {};
    hit.keys.forEach((k, i) => { params[k] = decodeURIComponent(m2[i + 1]); });

    const result = await hit.handler({ req, res, body, query: parsed.query, params, ip, session, token });
    if (res.writableEnded) return;
    if (result && result.ok === false) {
      const code = result.__code || 400;
      delete result.__code;
      return json(res, code, result);
    }
    return json(res, 200, result || { ok: true });
  } catch (e) {
    const code = e.code === 'E2BIG' ? 413 : (e.code === 'EPATH' ? 400 : 500);
    if (!res.writableEnded) json(res, code, { ok: false, error: e.message || 'خطأ داخلي' });
    if (code === 500) console.error('[panel]', new Date().toISOString(), e);
  }
});

const cfg = load();
const PORT = process.env.PORT || cfg.panel.port || 8088;
const BIND = process.env.BIND || cfg.panel.bind || '0.0.0.0';

server.listen(PORT, BIND, () => {
  console.log('[ShamHost] لوحة التحكم تعمل على http://' + system.lanIp() + ':' + PORT + '  (' + new Date().toISOString() + ')');
});

process.on('unhandledRejection', (e) => console.error('[panel] rejection:', e));
process.on('SIGTERM', () => { server.close(() => process.exit(0)); });
