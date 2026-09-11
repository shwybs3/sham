#!/usr/bin/env bash
# ShamHost — بيئة لينكس معزولة داخل Termux عبر proot-distro، بدون روت.
#
# متى تستخدم هذا؟ عندما تريد بيئة أقرب إلى خادم لينكس حقيقي (Debian كامل،
# apt، أسماء حزم قياسية، مسارات /etc و /var المعتادة) بدل بيئة Termux
# المختصرة — أو حين لا يمكن ترويت الجهاز. proot-distro يبني نظام ملفات
# Debian حقيقياً ويشغّله فوق نواة أندرويد **دون root فعلي** (يحاكيه proot).
#
# صريح وواضح: cPanel/WHM الرسمي لا يعمل هنا ولا في أي بيئة بلا root حقيقي
# و systemd و ترخيص مدفوع مربوط بـ IP. ما يُنصَّب هنا هو مكافئ وظيفي — لوحة
# ShamHost (تغطي ما يُستخدم فعلياً من cPanel) فوق مكدس LEMP كامل داخل Debian
# معزول. هذا هو الحد الأقصى الواقعي والصادق لـ «استضافة قوية جاهزة» على جوال.
#
# الاستخدام:  bash proot-setup.sh
set -uo pipefail

DISTRO="debian"
BLU=$'\033[36m'; GRN=$'\033[32m'; YLW=$'\033[33m'; RED=$'\033[31m'; BLD=$'\033[1m'; RST=$'\033[0m'
step() { printf '\n%s==>%s %s%s%s\n' "$BLU" "$RST" "$BLD" "$*" "$RST"; }
ok()   { printf '  %s✓%s %s\n' "$GRN" "$RST" "$*"; }
warn() { printf '  %s!%s %s\n' "$YLW" "$RST" "$*"; }
die()  { printf '\n%sخطأ:%s %s\n' "$RED" "$RST" "$*" >&2; exit 1; }

[ -n "${PREFIX:-}" ] && [ -d "$PREFIX/bin" ] || die "يعمل داخل Termux فقط. ثبّت Termux من F-Droid."
case "$PREFIX" in *com.termux*) : ;; *) die "لم يتم التعرف على Termux." ;; esac

step "تثبيت proot-distro"
pkg install -y proot-distro >/dev/null 2>&1 || die "تعذر تثبيت proot-distro. جرّب: pkg update && pkg upgrade"
ok "proot-distro جاهز"

step "تنصيب توزيعة $DISTRO المعزولة"
if proot-distro list --installed 2>/dev/null | grep -q "^${DISTRO}\b"; then
  ok "التوزيعة منصّبة مسبقاً — سيُعاد استخدامها"
else
  proot-distro install "$DISTRO" || die "فشل تنصيب $DISTRO"
  ok "تم تنصيب $DISTRO"
fi

# سكربت الإعداد الذي يُشغَّل *داخل* Debian المعزول.
INNER="$PREFIX/tmp/shamhost-inner-setup.sh"
cat > "$INNER" <<'INNEREOF'
#!/usr/bin/env bash
set -uo pipefail
export DEBIAN_FRONTEND=noninteractive
GRN=$'\033[32m'; BLU=$'\033[36m'; RST=$'\033[0m'
istep() { printf '\n%s[debian]%s %s\n' "$BLU" "$RST" "$*"; }
iok()   { printf '  %s✓%s %s\n' "$GRN" "$RST" "$*"; }

istep "تحديث الحزم"
apt-get update -y >/dev/null 2>&1

istep "تنصيب مكدس الاستضافة (nginx, php-fpm, mariadb, curl, cron)"
apt-get install -y --no-install-recommends \
  nginx php-fpm php-cli php-mysql php-mbstring php-curl php-xml php-zip php-gd \
  mariadb-server curl ca-certificates cron unzip git >/dev/null 2>&1
iok "المكدس منصّب"

# مجلد المواقع والصفحة الافتراضية
mkdir -p /srv/www/default
cat > /srv/www/default/index.php <<'PHPEOF'
<?php
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8">';
echo '<body style="font-family:sans-serif;background:#0b1120;color:#e5edf7;text-align:center;padding:60px 16px">';
echo '<h1>🟢 بيئة ShamHost المعزولة تعمل</h1>';
echo '<p>Debian + nginx + PHP ' . PHP_VERSION . ' + MariaDB — داخل Termux، بدون روت.</p>';
echo '<p>ضع مواقعك في <code>/srv/www</code> داخل التوزيعة المعزولة.</p></body></html>';
PHPEOF

# اكتشاف مقبس php-fpm ونسخته
FPM_BIN="$(ls /usr/sbin/php-fpm* 2>/dev/null | head -1)"
FPM_SOCK="$(ls /run/php/php*-fpm.sock 2>/dev/null | head -1)"
[ -z "$FPM_SOCK" ] && FPM_SOCK="/run/php/php-fpm.sock"

# vhost يستمع على 8080 (منافذ <1024 تحتاج امتيازات غير متاحة بلا روت)
cat > /etc/nginx/sites-available/default <<NGINXEOF
server {
    listen 8080 default_server;
    listen [::]:8080 default_server;
    root /srv/www/default;
    index index.php index.html;
    location / { try_files \$uri \$uri/ =404; }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${FPM_SOCK};
    }
}
NGINXEOF
iok "vhost على المنفذ 8080"

# سكربت تشغيل الخدمات داخل التوزيعة (لا systemd في proot)
cat > /usr/local/bin/shamhost-services <<SVCEOF
#!/usr/bin/env bash
# تشغيل mariadb + php-fpm + nginx يدوياً (بلا systemd داخل proot)
mkdir -p /run/php /run/mysqld /var/lib/mysql
chown -R mysql:mysql /run/mysqld /var/lib/mysql 2>/dev/null || true
if [ ! -d /var/lib/mysql/mysql ]; then
  mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 || \
  mysql_install_db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 || true
fi
pgrep -x mariadbd >/dev/null || (mariadbd --user=mysql >/var/log/mariadb.log 2>&1 &)
${FPM_BIN} --daemonize 2>/dev/null || service php-fpm start 2>/dev/null || true
nginx 2>/dev/null || service nginx restart 2>/dev/null || true
sleep 1
echo "الخدمات شغّالة. افتح: http://127.0.0.1:8080/"
SVCEOF
chmod +x /usr/local/bin/shamhost-services
iok "أمر التشغيل: shamhost-services"

echo
echo "════════════════════════════════════════════"
echo " بيئة Debian المعزولة جاهزة."
echo " لتشغيل الخدمات داخلها:  shamhost-services"
echo "════════════════════════════════════════════"
INNEREOF

step "إعداد المكدس داخل التوزيعة (قد يستغرق عدة دقائق)"
proot-distro login "$DISTRO" -- bash "$PREFIX/tmp/shamhost-inner-setup.sh" \
  || die "فشل الإعداد داخل التوزيعة"

# اختصار تشغيل من Termux مباشرة
LAUNCH="$PREFIX/bin/shamhost-proot"
cat > "$LAUNCH" <<LAUNCHEOF
#!/usr/bin/env bash
# دخول بيئة ShamHost المعزولة، وتشغيل الخدمات إن مُرّر: start
if [ "\${1:-}" = "start" ]; then
  exec proot-distro login "$DISTRO" -- shamhost-services
fi
exec proot-distro login "$DISTRO"
LAUNCHEOF
chmod +x "$LAUNCH"

rm -f "$INNER"

step "تم"
ok "الدخول للبيئة المعزولة:      shamhost-proot"
ok "تشغيل الخدمات فيها مباشرة:   shamhost-proot start"
ok "ثم افتح في المتصفح:          http://127.0.0.1:8080/"
echo
warn "cPanel الرسمي غير مدعوم (يحتاج root حقيقي + systemd + ترخيص). هذه بيئة"
warn "معزولة قوية تدير مواقعك عبر لوحة ShamHost أو يدوياً من /srv/www."
