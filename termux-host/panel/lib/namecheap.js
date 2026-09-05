'use strict';
/**
 * تكامل Namecheap — إدارة سجلات DNS والدومينات آلياً.
 *
 * متطلبات قبل الاستخدام (من حساب Namecheap):
 *   1. Profile > Tools > Namecheap API Access  →  فعّل API
 *   2. أضف عنوان IP الخارجي لهذا الجهاز في Whitelisted IPs
 *   3. انسخ API Key واسم المستخدم
 * ملاحظة جوهرية: أمر setHosts في Namecheap يستبدل كل السجلات دفعة واحدة،
 * لذلك نقرأ السجلات الحالية أولاً وندمج التغيير قبل الإرسال.
 */
const { httpsRequest, xmlFindAll, xmlText, isDomain } = require('./util');
const { load, update } = require('./config');

const PROD = 'https://api.namecheap.com/xml.response';
const SANDBOX = 'https://api.sandbox.namecheap.com/xml.response';

function splitDomain(domain) {
  const parts = String(domain).toLowerCase().split('.').filter(Boolean);
  if (parts.length < 2) return null;
  return { sld: parts[0], tld: parts.slice(1).join('.') };
}

async function publicIp() {
  for (const url of ['https://api.ipify.org', 'https://ifconfig.me/ip', 'https://icanhazip.com']) {
    try {
      const r = await httpsRequest(url, { timeout: 8000 });
      const ip = String(r.body || '').trim();
      if (/^\d{1,3}(\.\d{1,3}){3}$/.test(ip)) return ip;
    } catch (_) {}
  }
  return null;
}

function settings() {
  const nc = load().namecheap || {};
  return nc;
}

function configured() {
  const nc = settings();
  return !!(nc.api_user && nc.api_key && nc.username);
}

async function call(command, params = {}) {
  const nc = settings();
  if (!configured()) {
    return { ok: false, error: 'لم يتم ضبط بيانات Namecheap API. افتح الإعدادات وأدخل ApiUser و ApiKey واسم المستخدم.' };
  }
  let clientIp = nc.client_ip;
  if (!clientIp) {
    clientIp = await publicIp();
    if (clientIp) update(c => { c.namecheap.client_ip = clientIp; });
  }
  if (!clientIp) return { ok: false, error: 'تعذّر تحديد عنوان IP الخارجي المطلوب لواجهة Namecheap.' };

  const base = nc.sandbox ? SANDBOX : PROD;
  const body = new URLSearchParams(Object.assign({
    ApiUser: nc.api_user,
    ApiKey: nc.api_key,
    UserName: nc.username,
    ClientIp: clientIp,
    Command: command,
  }, params)).toString();

  let res;
  try {
    res = await httpsRequest(base, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Length': Buffer.byteLength(body),
      },
      body,
      timeout: 40000,
    });
  } catch (e) {
    return { ok: false, error: 'تعذّر الاتصال بـ Namecheap: ' + e.message };
  }

  const xml = res.body || '';
  const status = /<ApiResponse[^>]*Status="([^"]+)"/i.exec(xml);
  if (!status || status[1].toUpperCase() !== 'OK') {
    const errs = [];
    const re = /<Error[^>]*Number="(\d+)"[^>]*>([\s\S]*?)<\/Error>/gi;
    let m;
    while ((m = re.exec(xml))) errs.push(m[2].trim() + ' [' + m[1] + ']');
    let msg = errs.join(' | ') || xmlText(xml, 'Error') || 'رفضت Namecheap الطلب';
    if (/2019166|invalid.*ip|not.*whitelist/i.test(msg)) {
      msg += ' — تأكد من إضافة IP الحالي (' + clientIp + ') في قائمة Whitelisted IPs داخل حساب Namecheap.';
    }
    return { ok: false, error: msg, xml };
  }
  return { ok: true, xml };
}

async function getDomains() {
  const r = await call('namecheap.domains.getList', { PageSize: '100' });
  if (!r.ok) return r;
  const domains = xmlFindAll(r.xml, 'Domain').map(d => ({
    name: d.Name,
    expires: d.Expires,
    autoRenew: d.AutoRenew === 'true',
    locked: d.IsLocked === 'true',
    whoisGuard: d.WhoisGuard,
  })).filter(d => d.name);
  return { ok: true, domains };
}

async function getNameservers(domain) {
  const p = splitDomain(domain);
  if (!p) return { ok: false, error: 'دومين غير صالح' };
  const r = await call('namecheap.domains.dns.getList', { SLD: p.sld, TLD: p.tld });
  if (!r.ok) return r;
  const m = /<DomainDNSGetListResult[^>]*UsingOurDNS="([^"]*)"/i.exec(r.xml);
  const ns = [];
  const re = /<Nameserver>([\s\S]*?)<\/Nameserver>/gi;
  let x;
  while ((x = re.exec(r.xml))) ns.push(x[1].trim());
  return { ok: true, usingNamecheapDns: m ? m[1] === 'true' : null, nameservers: ns };
}

async function setCustomNameservers(domain, nameservers) {
  const p = splitDomain(domain);
  if (!p) return { ok: false, error: 'دومين غير صالح' };
  const list = (nameservers || []).map(s => String(s).trim().toLowerCase()).filter(Boolean);
  if (list.length < 2) return { ok: false, error: 'مطلوب خادما أسماء على الأقل' };
  const r = await call('namecheap.domains.dns.setCustom', { SLD: p.sld, TLD: p.tld, Nameservers: list.join(',') });
  return r.ok ? { ok: true } : r;
}

async function setDefaultNameservers(domain) {
  const p = splitDomain(domain);
  if (!p) return { ok: false, error: 'دومين غير صالح' };
  const r = await call('namecheap.domains.dns.setDefault', { SLD: p.sld, TLD: p.tld });
  return r.ok ? { ok: true } : r;
}

const VALID_TYPES = ['A', 'AAAA', 'ALIAS', 'CAA', 'CNAME', 'MX', 'MXE', 'NS', 'TXT', 'URL', 'URL301', 'FRAME'];

async function getHosts(domain) {
  const p = splitDomain(domain);
  if (!p) return { ok: false, error: 'دومين غير صالح' };
  const r = await call('namecheap.domains.dns.getHosts', { SLD: p.sld, TLD: p.tld });
  if (!r.ok) return r;
  const hosts = xmlFindAll(r.xml, 'host').map(h => ({
    name: h.Name,
    type: h.Type,
    address: h.Address,
    ttl: h.TTL || '1799',
    mxPref: h.MXPref || '10',
  })).filter(h => h.name && h.type);
  return { ok: true, hosts, isUsingOurDns: /IsUsingOurDNS="true"/i.test(r.xml) };
}

/** يستبدل كامل مجموعة السجلات — استخدمه فقط بعد قراءة السجلات الحالية */
async function setHosts(domain, records) {
  const p = splitDomain(domain);
  if (!p) return { ok: false, error: 'دومين غير صالح' };
  if (!Array.isArray(records) || records.length === 0) {
    return { ok: false, error: 'لا يمكن إرسال قائمة سجلات فارغة (سيمحو كل DNS).' };
  }
  const params = { SLD: p.sld, TLD: p.tld };
  records.forEach((rec, i) => {
    const n = i + 1;
    const type = String(rec.type || '').toUpperCase();
    if (!VALID_TYPES.includes(type)) throw new Error('نوع سجل غير مدعوم: ' + type);
    params['HostName' + n] = String(rec.name || '@').trim();
    params['RecordType' + n] = type;
    params['Address' + n] = String(rec.address || '').trim();
    params['TTL' + n] = String(parseInt(rec.ttl, 10) || 1799);
    if (type === 'MX') params['MXPref' + n] = String(parseInt(rec.mxPref, 10) || 10);
  });
  if (records.some(r => String(r.type).toUpperCase() === 'MX')) params.EmailType = 'MX';
  const r = await call('namecheap.domains.dns.setHosts', params);
  return r.ok ? { ok: true, count: records.length } : r;
}

/**
 * دمج آمن: يقرأ السجلات الحالية، يستبدل ما يطابق (الاسم + النوع)، ويحافظ على الباقي.
 * upserts: [{name,type,address,ttl,mxPref}]
 * removals: [{name,type}]
 */
async function applyRecords(domain, upserts = [], removals = []) {
  const cur = await getHosts(domain);
  if (!cur.ok) return cur;
  let hosts = cur.hosts.slice();

  const key = (r) => (String(r.name || '@').toLowerCase() + '|' + String(r.type || '').toUpperCase());
  const removeKeys = new Set(removals.map(key));
  const upsertKeys = new Set(upserts.map(key));

  hosts = hosts.filter(h => !removeKeys.has(key(h)) && !upsertKeys.has(key(h)));
  for (const u of upserts) {
    hosts.push({
      name: String(u.name || '@'),
      type: String(u.type).toUpperCase(),
      address: String(u.address || ''),
      ttl: String(parseInt(u.ttl, 10) || 1799),
      mxPref: String(parseInt(u.mxPref, 10) || 10),
    });
  }
  if (!hosts.length) return { ok: false, error: 'النتيجة ستكون بلا سجلات — أُلغيت العملية حمايةً للدومين.' };
  const res = await setHosts(domain, hosts);
  return res.ok ? { ok: true, hosts } : res;
}

/** ربط سريع: الدومين + www إلى عنوان IP (سجل A) أو إلى هدف (CNAME) */
async function pointTo(domain, target, opts = {}) {
  const isIp = /^\d{1,3}(\.\d{1,3}){3}$/.test(String(target).trim());
  const type = opts.type || (isIp ? 'A' : 'CNAME');
  const ttl = opts.ttl || 300;
  const ups = [{ name: '@', type: isIp ? 'A' : 'ALIAS', address: target, ttl }];
  if (opts.www !== false) ups.push({ name: 'www', type: isIp ? 'A' : 'CNAME', address: target, ttl });
  if (Array.isArray(opts.subdomains)) {
    for (const s of opts.subdomains) {
      if (s && /^[a-z0-9*_-]+$/i.test(s)) ups.push({ name: s, type: isIp ? 'A' : 'CNAME', address: target, ttl });
    }
  }
  const r = await applyRecords(domain, ups, []);
  return r.ok ? { ok: true, applied: ups, type } : r;
}

/** DNS Dynamic — لا يحتاج API key، فقط كلمة مرور DDNS من لوحة Namecheap */
async function ddnsUpdate(host, domain, password, ip) {
  if (!isDomain(domain)) return { ok: false, error: 'دومين غير صالح' };
  const url = 'https://dynamicdns.park-your-domain.com/update?' + new URLSearchParams({
    host: host || '@', domain, password, ip: ip || '',
  }).toString();
  try {
    const r = await httpsRequest(url, { timeout: 20000 });
    const errCount = xmlText(r.body, 'ErrCount');
    if (errCount && errCount !== '0') {
      const err = /<Err1>([\s\S]*?)<\/Err1>/i.exec(r.body);
      return { ok: false, error: err ? err[1].trim() : 'فشل تحديث DDNS' };
    }
    return { ok: true, ip: xmlText(r.body, 'IP') || ip };
  } catch (e) {
    return { ok: false, error: 'تعذّر الاتصال بخدمة DDNS: ' + e.message };
  }
}

module.exports = {
  configured, call, getDomains, getHosts, setHosts, applyRecords, pointTo,
  getNameservers, setCustomNameservers, setDefaultNameservers,
  ddnsUpdate, publicIp, splitDomain, VALID_TYPES,
};
