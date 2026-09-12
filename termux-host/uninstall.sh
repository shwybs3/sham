#!/usr/bin/env bash
# إزالة ShamHost
set -uo pipefail
HOSTDIR="$HOME/.shamhost"
BLD=$'\033[1m'; YLW=$'\033[33m'; RST=$'\033[0m'

printf '%sإزالة ShamHost%s\n\n' "$BLD" "$RST"

command -v shamhost >/dev/null 2>&1 && shamhost stop

rm -f "$PREFIX/bin/shamhost" "$PREFIX/bin/shamhost-service"
rm -f "$HOME/.termux/boot/00-shamhost"
printf '  ✓ أُزيلت الأوامر وسكربت الإقلاع\n'

printf '\n%sهل تحذف المواقع والإعدادات والنسخ الاحتياطية في %s؟%s\n' "$YLW" "$HOSTDIR" "$RST"
printf 'اكتب DELETE للحذف، أو Enter للإبقاء عليها: '
read -r ans
if [ "$ans" = "DELETE" ]; then
  rm -rf "$HOSTDIR"
  printf '  ✓ حُذف %s\n' "$HOSTDIR"
else
  printf '  • أُبقيت الملفات في %s\n' "$HOSTDIR"
fi

printf '\nحزم النظام (nginx, php, mariadb, nodejs) لم تُحذف.\n'
printf 'لحذفها: pkg uninstall nginx php php-fpm mariadb\n'
