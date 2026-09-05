'use strict';
const crypto = require('crypto');
const cfgMod = require('./config');
const { hashPassword, timingSafeEq } = require('./util');

const sessions = new Map();          // token -> { user, created, lastSeen, ip }
const attempts = new Map();          // ip -> { count, until }
const SESSION_TTL = 12 * 60 * 60 * 1000;
const MAX_ATTEMPTS = 6;
const LOCK_MS = 10 * 60 * 1000;

function purge() {
  const now = Date.now();
  for (const [t, s] of sessions) if (now - s.lastSeen > SESSION_TTL) sessions.delete(t);
  for (const [ip, a] of attempts) if (now > a.until) attempts.delete(ip);
}
setInterval(purge, 5 * 60 * 1000).unref();

function locked(ip) {
  const a = attempts.get(ip);
  return !!(a && a.count >= MAX_ATTEMPTS && Date.now() < a.until);
}

function noteFailure(ip) {
  const a = attempts.get(ip) || { count: 0, until: 0 };
  a.count += 1;
  a.until = Date.now() + LOCK_MS;
  attempts.set(ip, a);
  return MAX_ATTEMPTS - a.count;
}

function login(username, password, ip) {
  if (locked(ip)) return { ok: false, error: 'محاولات كثيرة فاشلة. انتظر 10 دقائق ثم أعد المحاولة.' };
  const cfg = cfgMod.load();
  const a = cfg.admin || {};
  if (!a.hash || !a.salt) return { ok: false, error: 'لم يتم ضبط كلمة مرور. نفّذ في الطرفية: shamhost password' };

  const userOk = timingSafeEq(String(username || ''), a.username || 'admin');
  let passOk = false;
  try { passOk = timingSafeEq(hashPassword(String(password || ''), a.salt), a.hash); } catch (_) { passOk = false; }

  if (!userOk || !passOk) {
    const left = noteFailure(ip);
    return { ok: false, error: 'بيانات دخول غير صحيحة' + (left > 0 && left <= 3 ? ' (' + left + ' محاولات متبقية)' : '') };
  }
  attempts.delete(ip);
  const token = crypto.randomBytes(32).toString('hex');
  sessions.set(token, { user: a.username || 'admin', created: Date.now(), lastSeen: Date.now(), ip });
  return { ok: true, token, user: a.username || 'admin' };
}

function verify(token) {
  if (!token) return null;
  const s = sessions.get(token);
  if (!s) return null;
  if (Date.now() - s.lastSeen > SESSION_TTL) { sessions.delete(token); return null; }
  s.lastSeen = Date.now();
  return s;
}

function logout(token) { sessions.delete(token); }
function logoutAll() { sessions.clear(); }

function setPassword(newPass) {
  if (!newPass || String(newPass).length < 8) return { ok: false, error: 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' };
  const salt = crypto.randomBytes(16).toString('hex');
  cfgMod.update(c => { c.admin.salt = salt; c.admin.hash = hashPassword(String(newPass), salt); });
  logoutAll();
  return { ok: true };
}

/** مفتاح API لواجهة cPanel المتوافقة */
function verifyApiToken(user, token) {
  const cfg = cfgMod.load();
  if (!cfg.api_token) return false;
  if (String(user) !== (cfg.admin.username || 'admin')) return false;
  try { return timingSafeEq(String(token), cfg.api_token); } catch (_) { return false; }
}

module.exports = { login, verify, logout, logoutAll, setPassword, verifyApiToken, sessions };
