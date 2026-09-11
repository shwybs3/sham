#!/usr/bin/env bash
# ShamHost — تثبيت بأمر واحد داخل Termux.
#
#   curl -fsSL https://raw.githubusercontent.com/shwybs3/sham/claude/web-app-hosting-domain-1enxch/termux-host/bootstrap.sh | bash
#
# يجلب المشروع ثم يشغّل install.sh. آمن لإعادة التشغيل: يحدّث نسخة موجودة بدل تكرارها،
# ويحافظ على إعداداتك وكلمة مرور اللوحة في ~/.shamhost.
set -uo pipefail

REPO="${SHAMHOST_REPO:-https://github.com/shwybs3/sham.git}"
BRANCH="${SHAMHOST_BRANCH:-claude/web-app-hosting-domain-1enxch}"
SRC="${SHAMHOST_SRC:-$HOME/sham}"

RED=$'\033[31m'; GRN=$'\033[32m'; YLW=$'\033[33m'; BLU=$'\033[36m'; BLD=$'\033[1m'; RST=$'\033[0m'
step() { printf '\n%s==>%s %s%s%s\n' "$BLU" "$RST" "$BLD" "$*" "$RST"; }
ok()   { printf '  %s✓%s %s\n' "$GRN" "$RST" "$*"; }
warn() { printf '  %s!%s %s\n' "$YLW" "$RST" "$*"; }
die()  { printf '\n%sخطأ:%s %s\n\n' "$RED" "$RST" "$*" >&2; exit 1; }

printf '\n%s🗄️  ShamHost — تثبيت تلقائي%s\n' "$BLD" "$RST"

[ -n "${PREFIX:-}" ] && case "$PREFIX" in *com.termux*) ;; *) die "هذا السكربت يعمل داخل Termux فقط." ;; esac
[ -n "${PREFIX:-}" ] || die "هذا السكربت يعمل داخل Termux فقط."

step "تجهيز أدوات الجلب"
export DEBIAN_FRONTEND=noninteractive
if ! command -v git >/dev/null 2>&1 || ! command -v curl >/dev/null 2>&1; then
  yes "" | pkg update -y >/dev/null 2>&1 || warn "تعذّر تحديث المستودعات، سنكمل بالمتاح"
  pkg install -y git curl >/dev/null 2>&1 || die "تعذّر تثبيت git و curl. تحقق من الاتصال ثم أعد المحاولة."
fi
ok "git و curl جاهزان"

step "جلب ShamHost (الفرع $BRANCH)"
if [ -d "$SRC/.git" ]; then
  git -C "$SRC" remote set-url origin "$REPO" 2>/dev/null
  if git -C "$SRC" fetch --depth 1 origin "$BRANCH" >/dev/null 2>&1 \
     && git -C "$SRC" checkout -q -B "$BRANCH" FETCH_HEAD >/dev/null 2>&1; then
    ok "حُدّثت النسخة الموجودة في $SRC"
  else
    warn "فشل التحديث — سنعيد الاستنساخ من جديد"
    rm -rf "$SRC"
  fi
fi
if [ ! -d "$SRC/.git" ]; then
  rm -rf "$SRC"
  git clone --depth 1 --branch "$BRANCH" "$REPO" "$SRC" >/dev/null 2>&1 \
    || die "تعذّر استنساخ المستودع. تحقق من الاتصال بالإنترنت."
  ok "استُنسخ إلى $SRC"
fi

[ -f "$SRC/termux-host/install.sh" ] || die "النسخة المجلوبة لا تحتوي termux-host/install.sh"

step "تشغيل المثبّت"
exec bash "$SRC/termux-host/install.sh"
