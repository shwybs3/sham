#!/usr/bin/env bash
# ShamHost — تحويل جهاز أندرويد إلى استضافة ويب كاملة عبر Termux، بدون روت.
# الاستخدام:  bash install.sh
set -uo pipefail

VERSION="1.0.0"
HOSTDIR="$HOME/.shamhost"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

RED=$'\033[31m'; GRN=$'\033[32m'; YLW=$'\033[33m'; BLU=$'\033[36m'; BLD=$'\033[1m'; RST=$'\033[0m'
say()  { printf '%s\n' "$*"; }
step() { printf '\n%s==>%s %s%s%s\n' "$BLU" "$RST" "$BLD" "$*" "$RST"; }
ok()   { printf '  %s✓%s %s\n' "$GRN" "$RST" "$*"; }
warn() { printf '  %s!%s %s\n' "$YLW" "$RST" "$*"; }
die()  { printf '\n%sخطأ:%s %s\n' "$RED" "$RST" "$*" >&2; exit 1; }

# ─────────────────────────── 1. فحوصات أولية ───────────────────────────
step "فحص البيئة"

[ -n "${PREFIX:-}" ] && [ -d "$PREFIX/bin" ] || die "هذا السكربت يعمل داخل Termux فقط. ثبّت Termux من F-Droid (وليس من Google Play)."
case "$PREFIX" in
  *com.termux*) ok "Termux مكتشف: $PREFIX" ;;
  *) die "لم يتم التعرف على Termux. PREFIX=$PREFIX" ;;
esac

if [ "$(id -u)" = "0" ]; then
  warn "أنت تعمل كـ root. ShamHost مصمم للعمل بدون روت، وسيكمل التثبيت على أي حال."
else
  ok "بدون روت (المستخدم: $(id -un))"
fi

ARCH="$(uname -m)"
ok "المعالج: $ARCH"

AVAIL_MB=$(df -Pm "$HOME" 2>/dev/null | awk 'NR==2{print $4}')
if [ -n "${AVAIL_MB:-}" ] && [ "$AVAIL_MB" -lt 1500 ]; then
  warn "المساحة المتاحة ${AVAIL_MB}MB. يُنصح بـ 2GB على الأقل."
else
  ok "المساحة المتاحة: ${AVAIL_MB:-غير معروف}MB"
fi

# ─────────────────────────── 2. حزم النظام ───────────────────────────
step "تحديث مستودعات Termux"
export DEBIAN_FRONTEND=noninteractive
yes "" | pkg update -y >/dev/null 2>&1 || warn "فشل تحديث المستودعات، سنكمل بالمتاح."
ok "المستودعات محدّثة"

PKGS=(nginx php php-fpm mariadb nodejs-lts openssl-tool curl wget git tar unzip procps termux-api cronie openssh)
step "تثبيت الحزم (${#PKGS[@]} حزمة) — قد يستغرق عدة دقائق"
FAILED=()
for p in "${PKGS[@]}"; do
  if pkg list-installed 2>/dev/null | grep -q "^${p}/"; then
    ok "$p (مثبّت مسبقاً)"
    continue
  fi
  if pkg install -y "$p" >/dev/null 2>&1; then
    ok "$p"
  else
    FAILED+=("$p"); warn "$p — فشل التثبيت"
  fi
done

# nodejs-lts قد يكون باسم nodejs
if ! command -v node >/dev/null 2>&1; then
  pkg install -y nodejs >/dev/null 2>&1 && ok "nodejs (بديل)" || die "تعذّر تثبيت Node.js وهو مطلوب للوحة التحكم."
fi
command -v nginx >/dev/null 2>&1 || die "تعذّر تثبيت nginx."
command -v php   >/dev/null 2>&1 || warn "PHP غير متاح — مواقع PHP لن تعمل، لكن المواقع الثابتة و Node ستعمل."

NODE_V="$(node -v 2>/dev/null || echo '?')"
ok "Node.js $NODE_V"

# cloudflared: من المستودع إن وُجد، وإلا تنزيل مباشر
step "تجهيز Cloudflare Tunnel (لنشر الموقع على الإنترنت بدون IP ثابت)"
if command -v cloudflared >/dev/null 2>&1; then
  ok "cloudflared مثبّت مسبقاً"
else
  pkg install -y cloudflared >/dev/null 2>&1 || true
  if ! command -v cloudflared >/dev/null 2>&1; then
    pkg install -y tur-repo >/dev/null 2>&1 && pkg install -y cloudflared >/dev/null 2>&1 || true
  fi
  if ! command -v cloudflared >/dev/null 2>&1; then
    case "$ARCH" in
      aarch64|arm64) CFARCH=arm64 ;;
      armv7l|armv8l|arm) CFARCH=arm ;;
      x86_64) CFARCH=amd64 ;;
      *) CFARCH="" ;;
    esac
    if [ -n "$CFARCH" ]; then
      if curl -fsSL --retry 3 -o "$PREFIX/bin/cloudflared" \
        "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-$CFARCH"; then
        chmod +x "$PREFIX/bin/cloudflared"; ok "cloudflared نُزّل مباشرة ($CFARCH)"
      else
        rm -f "$PREFIX/bin/cloudflared"; warn "تعذّر تنزيل cloudflared — يمكنك تثبيته لاحقاً من اللوحة."
      fi
    else
      warn "معمارية غير مدعومة لـ cloudflared: $ARCH"
    fi
  else
    ok "cloudflared مثبّت"
  fi
fi

# acme.sh لشهادات SSL مجانية عبر DNS
step "تجهيز acme.sh (شهادات SSL مجانية من Let's Encrypt)"
if [ -x "$HOME/.acme.sh/acme.sh" ]; then
  ok "acme.sh مثبّت مسبقاً"
else
  if curl -fsSL --retry 3 https://get.acme.sh -o "$HOSTDIR-acme-install.sh" 2>/dev/null; then
    sh "$HOSTDIR-acme-install.sh" --home "$HOME/.acme.sh" >/dev/null 2>&1 \
      && ok "acme.sh مثبّت" || warn "تعذّر تثبيت acme.sh — يمكن تفعيل SSL لاحقاً."
    rm -f "$HOSTDIR-acme-install.sh"
  else
    warn "تعذّر تنزيل acme.sh (تحقق من الاتصال) — يمكن تثبيته لاحقاً من اللوحة."
  fi
fi

# ─────────────────────────── 3. بنية المجلدات ───────────────────────────
step "إنشاء بنية المجلدات في $HOSTDIR"
mkdir -p "$HOSTDIR"/{conf/vhosts,sites,logs,run,backups,ssl,data,tmp,panel,tunnels}
mkdir -p "$HOSTDIR"/run/nginx/{client_body,proxy,fastcgi,uwsgi,scgi}
chmod 700 "$HOSTDIR/data" "$HOSTDIR/ssl"
ok "المجلدات جاهزة"

# ─────────────────────────── 4. نسخ ملفات اللوحة ───────────────────────────
step "تثبيت لوحة التحكم"
rm -rf "$HOSTDIR/panel"
cp -r "$SRC/panel" "$HOSTDIR/panel"
cp "$SRC/bin/shamhost" "$PREFIX/bin/shamhost" && chmod +x "$PREFIX/bin/shamhost"
cp "$SRC/bin/shamhost-service" "$PREFIX/bin/shamhost-service" && chmod +x "$PREFIX/bin/shamhost-service"
ok "اللوحة والأوامر مثبّتة (shamhost)"

# ─────────────────────────── 5. إعداد MariaDB ───────────────────────────
step "تهيئة قاعدة البيانات MariaDB"
DB_OK=0
if command -v mysqld_safe >/dev/null 2>&1 || command -v mariadbd-safe >/dev/null 2>&1; then
  if [ ! -d "$PREFIX/var/lib/mysql/mysql" ]; then
    (mysql_install_db >/dev/null 2>&1 || mariadb-install-db >/dev/null 2>&1) && ok "قاعدة البيانات مُهيّأة" \
      || warn "فشل mysql_install_db — قد تكون مهيّأة مسبقاً"
  else
    ok "قاعدة البيانات مُهيّأة مسبقاً"
  fi
  DB_OK=1
else
  warn "MariaDB غير متاح — المواقع التي تحتاج قاعدة بيانات لن تعمل حتى تثبّته: pkg install mariadb"
fi

# ─────────────────────────── 6. ملفات الإعداد ───────────────────────────
step "كتابة إعدادات nginx و PHP-FPM"

cat > "$HOSTDIR/conf/nginx.conf" <<NGINX
# ShamHost — يُولَّد تلقائياً. لا تعدّل يدوياً؛ استخدم لوحة التحكم.
worker_processes  1;
daemon            off;
pid               $HOSTDIR/run/nginx.pid;
error_log         $HOSTDIR/logs/nginx-error.log warn;

events { worker_connections 512; }

http {
    include             $PREFIX/etc/nginx/mime.types;
    default_type        application/octet-stream;
    sendfile            on;
    tcp_nopush          on;
    keepalive_timeout   65;
    server_tokens       off;
    client_max_body_size 256M;

    client_body_temp_path $HOSTDIR/run/nginx/client_body;
    proxy_temp_path       $HOSTDIR/run/nginx/proxy;
    fastcgi_temp_path     $HOSTDIR/run/nginx/fastcgi;
    uwsgi_temp_path       $HOSTDIR/run/nginx/uwsgi;
    scgi_temp_path        $HOSTDIR/run/nginx/scgi;

    log_format shamhost '\$remote_addr - [\$time_local] "\$request" \$status \$body_bytes_sent "\$http_referer" "\$http_user_agent"';
    access_log $HOSTDIR/logs/nginx-access.log shamhost;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml image/svg+xml;
    gzip_min_length 1024;

    # الموقع الافتراضي — يردّ على أي دومين غير معرّف
    server {
        listen 8080 default_server;
        server_name _;
        root $HOSTDIR/sites/_default;
        index index.html index.php;
        location / { try_files \$uri \$uri/ =404; }
    }

    include $HOSTDIR/conf/vhosts/*.conf;
}
NGINX
ok "nginx.conf"

cat > "$HOSTDIR/conf/php-fpm.conf" <<PHPFPM
; ShamHost — إعداد PHP-FPM
[global]
pid   = $HOSTDIR/run/php-fpm.pid
error_log = $HOSTDIR/logs/php-fpm.log
daemonize = no

[www]
listen = 127.0.0.1:9000
listen.backlog = 128
pm = dynamic
pm.max_children = 8
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 400
php_admin_value[upload_max_filesize] = 128M
php_admin_value[post_max_size] = 128M
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 120
php_admin_value[error_log] = $HOSTDIR/logs/php-error.log
php_admin_flag[log_errors] = on
catch_workers_output = yes
PHPFPM
ok "php-fpm.conf"

# صفحة افتراضية
mkdir -p "$HOSTDIR/sites/_default"
cat > "$HOSTDIR/sites/_default/index.html" <<'DEFPAGE'
<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8">
<title>ShamHost</title>
<style>body{font-family:system-ui,sans-serif;background:#0d1117;color:#e6edf3;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center}
.c{max-width:34rem;padding:2rem}h1{margin:.2rem 0}code{background:#161b22;padding:.15rem .45rem;border-radius:.35rem}</style>
<div class="c"><h1>ShamHost يعمل ✅</h1>
<p>خادمك يعمل الآن على هذا الجهاز. لم يتم ربط أي دومين بهذا الطلب بعد.</p>
<p>افتح لوحة التحكم وأضف موقعك الأول.</p></div>
DEFPAGE
ok "الصفحة الافتراضية"

# ─────────────────────────── 7. الأسرار وملف الإعداد ───────────────────────────
step "توليد بيانات الدخول"
rnd() { head -c "$1" /dev/urandom | od -An -tx1 | tr -d ' \n'; }

CFG="$HOSTDIR/data/config.json"
if [ -f "$CFG" ]; then
  ok "ملف الإعداد موجود — تم الحفاظ على كلمة المرور الحالية"
  ADMIN_PASS=""
else
  ADMIN_PASS="$(rnd 9)"
  SESSION_SECRET="$(rnd 32)"
  API_TOKEN="$(rnd 24)"
  DB_ADMIN_USER="$(id -un)"
  SALT="$(rnd 16)"
  HASH="$(node -e "const c=require('crypto');process.stdout.write(c.pbkdf2Sync(process.argv[1],process.argv[2],120000,32,'sha256').toString('hex'))" "$ADMIN_PASS" "$SALT")"
  cat > "$CFG" <<JSON
{
  "version": "$VERSION",
  "created_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "admin": { "username": "admin", "salt": "$SALT", "hash": "$HASH" },
  "session_secret": "$SESSION_SECRET",
  "api_token": "$API_TOKEN",
  "panel": { "port": 8088, "bind": "0.0.0.0" },
  "web": { "http_port": 8080, "https_port": 8443 },
  "db": { "admin_user": "$DB_ADMIN_USER", "admin_pass": "", "host": "127.0.0.1", "port": 3306 },
  "namecheap": { "api_user": "", "api_key": "", "username": "", "client_ip": "", "sandbox": false },
  "cloudflare": { "token": "" },
  "sites": [],
  "tunnels": [],
  "ddns": []
}
JSON
  chmod 600 "$CFG"
  ok "بيانات الدخول جاهزة"
fi

# ─────────────────────────── 8. سكربت الإقلاع التلقائي ───────────────────────────
step "تفعيل التشغيل التلقائي عند إعادة تشغيل الجهاز"
mkdir -p "$HOME/.termux/boot"
cat > "$HOME/.termux/boot/00-shamhost" <<'BOOT'
#!/data/data/com.termux/files/usr/bin/sh
termux-wake-lock 2>/dev/null
sleep 5
shamhost start >> "$HOME/.shamhost/logs/boot.log" 2>&1
BOOT
chmod +x "$HOME/.termux/boot/00-shamhost"
ok "سكربت الإقلاع مكتوب (ثبّت تطبيق Termux:Boot من F-Droid لتفعيله)"

# ─────────────────────────── 9. التشغيل ───────────────────────────
step "تشغيل الخدمات"
termux-wake-lock 2>/dev/null && ok "قفل الاستيقاظ مُفعّل (الجهاز لن يوقف الخادم عند إطفاء الشاشة)" || warn "termux-wake-lock غير متاح"

shamhost start || warn "بعض الخدمات لم تبدأ — نفّذ 'shamhost status' للتفاصيل"

sleep 3
LANIP="$(ip route get 1.1.1.1 2>/dev/null | awk '{for(i=1;i<=NF;i++) if($i=="src") print $(i+1); exit}')"
[ -z "${LANIP:-}" ] && LANIP="$(ifconfig 2>/dev/null | awk '/inet /{print $2}' | grep -v '^127' | head -1)"
[ -z "${LANIP:-}" ] && LANIP="عنوان-الجهاز"

printf '\n%s────────────────────────────────────────────%s\n' "$GRN" "$RST"
printf '%s  ShamHost %s — التثبيت اكتمل%s\n' "$BLD" "$VERSION" "$RST"
printf '%s────────────────────────────────────────────%s\n\n' "$GRN" "$RST"
printf '  لوحة التحكم:  %shttp://%s:8088%s\n' "$BLD" "$LANIP" "$RST"
printf '  من نفس الجهاز: %shttp://127.0.0.1:8088%s\n' "$BLD" "$RST"
printf '  المستخدم:      %sadmin%s\n' "$BLD" "$RST"
if [ -n "$ADMIN_PASS" ]; then
  printf '  كلمة المرور:   %s%s%s   ← احفظها الآن\n' "$BLD" "$ADMIN_PASS" "$RST"
else
  printf '  كلمة المرور:   (كما هي من التثبيت السابق — لإعادة تعيينها: shamhost password)\n'
fi
printf '\n  خادم الويب:    http://%s:8080\n' "$LANIP"
printf '  أوامر مفيدة:   shamhost status | logs | restart | password | url\n\n'
printf '  الخطوة التالية: افتح اللوحة ← "المواقع" ← أضف موقعاً، ثم "الدومينات" لربط دومين نيم شيب.\n\n'
if [ ${#FAILED[@]} -gt 0 ]; then
  warn "حزم لم تُثبّت: ${FAILED[*]} — أعد المحاولة بـ: pkg install ${FAILED[*]}"
fi
