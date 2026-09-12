'use strict';
/** إدارة المهام المجدولة عبر crontab (حزمة cronie في Termux) */
const { run } = require('./util');

const MARK = '# shamhost';

async function raw() {
  const r = await run('crontab', ['-l'], { timeout: 15000 });
  if (!r.ok && /no crontab/i.test(r.stderr || '')) return '';
  return r.ok ? r.stdout : '';
}

async function list() {
  const txt = await raw();
  const out = [];
  txt.split('\n').forEach((line, i) => {
    const t = line.trim();
    if (!t || t.startsWith('#')) return;
    const m = /^((?:@\w+)|(?:\S+\s+\S+\s+\S+\s+\S+\s+\S+))\s+(.*)$/.exec(t);
    if (!m) return;
    out.push({ id: i, schedule: m[1], command: m[2], line: t });
  });
  return out;
}

function validSchedule(s) {
  s = String(s || '').trim();
  if (/^@(reboot|yearly|annually|monthly|weekly|daily|midnight|hourly)$/.test(s)) return true;
  return /^(\S+\s+){4}\S+$/.test(s);
}

async function writeAll(lines) {
  const body = lines.join('\n').replace(/\n*$/, '\n');
  const r = await run('crontab', ['-'], { input: body, timeout: 15000 });
  return r.ok ? { ok: true } : { ok: false, error: (r.stderr || r.stdout || 'فشل حفظ الجدول').slice(-500) };
}

async function add(schedule, command, comment) {
  if (!validSchedule(schedule)) return { ok: false, error: 'صيغة الجدولة غير صحيحة. مثال: 0 3 * * *' };
  if (!String(command || '').trim()) return { ok: false, error: 'الأمر مطلوب' };
  if (/[\r\n]/.test(String(command))) return { ok: false, error: 'الأمر يجب أن يكون سطراً واحداً' };
  const txt = await raw();
  const lines = txt.split('\n').filter(l => l.trim() !== '');
  if (comment) lines.push(MARK + ' ' + String(comment).replace(/[\r\n]/g, ' '));
  lines.push(String(schedule).trim() + ' ' + String(command).trim());
  return writeAll(lines);
}

async function remove(id) {
  const txt = await raw();
  const lines = txt.split('\n');
  const i = parseInt(id, 10);
  if (!Number.isInteger(i) || i < 0 || i >= lines.length) return { ok: false, error: 'المهمة غير موجودة' };
  lines.splice(i, 1);
  return writeAll(lines.filter(l => l.trim() !== ''));
}

async function replaceAll(text) {
  return writeAll(String(text || '').split('\n'));
}

module.exports = { list, add, remove, raw, replaceAll, validSchedule };
