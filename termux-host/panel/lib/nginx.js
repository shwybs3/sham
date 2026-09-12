'use strict';
const fs = require('fs');
const path = require('path');
const { P, load } = require('./config');
const { run, isDomain } = require('./util');

const PREFIX = process.env.PREFIX || '/data/data/com.termux/files/usr';

function vhostPath(site) { return path.join(P.VHOSTS, site.id + '.conf'); }

function serverNames(site) {
  const names = [site.domain].concat(site.aliases || []).filter(isDomain);
  return Array.from(new Set(names)).join(' ');
}

/** توليد ملف vhost — كل القيم المتغيرة مُتحقق منها قبل الوصول إلى هنا */
function render(site, cfg) {
  const httpPort = (cfg.web && cfg.web.http_port) || 8080;
  const httpsPort = (cfg.web && cfg.web.https_port) || 8443;
  const root = site.root;
  const names = serverNames(site);
  const sslDir = path.join(P.SSL, site.domain);
  const hasSsl = site.ssl && fs.existsSync(path.join(sslDir, 'fullchain.pem')) && fs.existsSync(path.join(sslDir, 'privkey.pem'));

  const common = [
    `    access_log ${path.join(P.LOGS, 'site-' + site.id + '-access.log')} shamhost;`,
    `    error_log  ${path.join(P.LOGS, 'site-' + site.id + '-error.log')} warn;`,
    `    charset utf-8;`,
    site.max_body ? `    client_max_body_size ${parseInt(site.max_body, 10) || 256}M;` : '',
    `    location ~ /\\.(?!well-known) { deny all; }`,
  ].filter(Boolean).join('\n');

  let body;
  if (site.type === 'proxy') {
    const port = parseInt(site.proxy_port, 10) || 3000;
    body = [
      `    location / {`,
      `        proxy_pass http://127.0.0.1:${port};`,
      `        proxy_http_version 1.1;`,
      `        proxy_set_header Upgrade $http_upgrade;`,
      `        proxy_set_header Connection "upgrade";`,
      `        proxy_set_header Host $host;`,
      `        proxy_set_header X-Real-IP $remote_addr;`,
      `        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;`,
      `        proxy_set_header X-Forwarded-Proto $scheme;`,
      `        proxy_read_timeout 120s;`,
      `    }`,
    ].join('\n');
  } else if (site.type === 'php') {
    body = [
      `    root ${root};`,
      `    index index.php index.html index.htm;`,
      `    location / { try_files $uri $uri/ /index.php?$query_string; }`,
      `    location ~ \\.php$ {`,
      `        try_files $uri =404;`,
      `        fastcgi_pass 127.0.0.1:9000;`,
      `        fastcgi_index index.php;`,
      `        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;`,
      `        include ${PREFIX}/etc/nginx/fastcgi_params;`,
      `        fastcgi_read_timeout 120s;`,
      `    }`,
      `    location ~* \\.(jpg|jpeg|png|gif|webp|svg|ico|css|js|woff2?|ttf|mp4)$ {`,
      `        expires 30d; access_log off; try_files $uri =404;`,
      `    }`,
    ].join('\n');
  } else {
    body = [
      `    root ${root};`,
      `    index index.html index.htm;`,
      `    location / { try_files $uri $uri/ ${site.spa ? '/index.html' : '=404'}; }`,
      `    location ~* \\.(jpg|jpeg|png|gif|webp|svg|ico|css|js|woff2?|ttf|mp4)$ {`,
      `        expires 30d; access_log off; try_files $uri =404;`,
      `    }`,
    ].join('\n');
  }

  const out = [];
  out.push(`# ShamHost vhost — ${site.domain} (${site.id})`);
  out.push(`# يُولَّد تلقائياً — أي تعديل يدوي سيُفقد عند الحفظ من اللوحة.`);
  out.push(`server {`);
  out.push(`    listen ${httpPort};`);
  out.push(`    server_name ${names};`);
  out.push(common);
  if (hasSsl && site.force_https) {
    out.push(`    location /.well-known/acme-challenge/ { root ${root}; }`);
    out.push(`    location / { return 301 https://$host:${httpsPort}$request_uri; }`);
  } else {
    out.push(body);
  }
  out.push(`}`);

  if (hasSsl) {
    out.push(``);
    out.push(`server {`);
    out.push(`    listen ${httpsPort} ssl;`);
    out.push(`    server_name ${names};`);
    out.push(`    ssl_certificate     ${path.join(sslDir, 'fullchain.pem')};`);
    out.push(`    ssl_certificate_key ${path.join(sslDir, 'privkey.pem')};`);
    out.push(`    ssl_protocols TLSv1.2 TLSv1.3;`);
    out.push(`    ssl_prefer_server_ciphers off;`);
    out.push(`    ssl_session_cache shared:SSL:2m;`);
    out.push(`    add_header Strict-Transport-Security "max-age=31536000" always;`);
    out.push(common);
    out.push(body);
    out.push(`}`);
  }
  out.push('');
  return out.join('\n');
}

function writeVhost(site) {
  const cfg = load();
  if (site.enabled === false) { removeVhost(site); return; }
  fs.writeFileSync(vhostPath(site), render(site, cfg));
}

function removeVhost(site) {
  try { fs.unlinkSync(vhostPath(site)); } catch (_) {}
}

/** هل nginx متاح على هذا الجهاز أصلاً؟ */
function available() {
  try {
    return !!require('child_process').execSync('command -v nginx 2>/dev/null').toString().trim();
  } catch (_) { return false; }
}

async function test() {
  if (!available()) return { ok: true, missing: true, output: 'nginx غير مثبّت — لم يُجرَ الفحص' };
  const r = await run('nginx', ['-t', '-c', path.join(P.CONF, 'nginx.conf'), '-p', PREFIX], { timeout: 20000 });
  return { ok: r.ok, output: (r.stderr || '') + (r.stdout || '') };
}

async function reload() {
  if (!available()) {
    return { ok: true, missing: true, output: 'nginx غير مثبّت — حُفظ الإعداد وسيُطبَّق عند تثبيته وتشغيله' };
  }
  const t = await test();
  if (!t.ok) return { ok: false, error: 'إعداد nginx غير صالح', output: t.output };
  const r = await run('shamhost', ['reload'], { timeout: 30000 });
  if (!r.ok) {
    const r2 = await run('shamhost', ['service', 'nginx', 'restart'], { timeout: 40000 });
    return { ok: r2.ok, output: r2.stdout + r2.stderr };
  }
  return { ok: true, output: r.stdout };
}

/** يعيد بناء كل ملفات vhost من الإعداد ثم يعيد التحميل */
async function rebuildAll() {
  const cfg = load();
  for (const f of fs.readdirSync(P.VHOSTS)) {
    if (f.endsWith('.conf')) { try { fs.unlinkSync(path.join(P.VHOSTS, f)); } catch (_) {} }
  }
  for (const s of cfg.sites) if (s.enabled !== false) fs.writeFileSync(vhostPath(s), render(s, cfg));
  return reload();
}

module.exports = { render, writeVhost, removeVhost, reload, test, rebuildAll, vhostPath, available };
