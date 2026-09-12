#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════
# YASSOTA — بناء APK إصدار موقّع بلا Gradle
#   aapt2 → javac → d8 → zipalign → apksigner
# الأسرار (كلمات مرور المفتاح) لا تُكتب في git إطلاقاً.
# ═══════════════════════════════════════════════════════════════
set -euo pipefail
cd "$(dirname "$0")"

APP_ID="com.yassota.app"
VERSION_NAME="${VERSION_NAME:-1.0.0}"
VERSION_CODE="${VERSION_CODE:-1}"
MIN_API="${MIN_API:-24}"
TARGET_API="${TARGET_API:-34}"
BT_VER="${BT_VER:-35.0.0}"          # 34 وما دونه: d8 ينهار على الفئات المجهولة تحت JDK 21
OUT="build"
APK="YASSOTA-${VERSION_NAME}.apk"

# ── 1) تحديد SDK ──────────────────────────────────────────────
SDK="${ANDROID_HOME:-${ANDROID_SDK_ROOT:-$HOME/android-sdk}}"
[ -d "$SDK" ] || { echo "✗ لم أجد Android SDK. اضبط ANDROID_HOME"; exit 1; }
BT="$SDK/build-tools/$BT_VER"
[ -d "$BT" ] || { echo "✗ لم أجد build-tools $BT_VER في $SDK/build-tools"; ls "$SDK/build-tools" 2>/dev/null; exit 1; }
JAR="$SDK/platforms/android-$TARGET_API/android.jar"
[ -f "$JAR" ] || { echo "✗ لم أجد android.jar للمنصة android-$TARGET_API"; exit 1; }

echo "▸ SDK=$SDK  build-tools=$BT_VER  target=$TARGET_API  min=$MIN_API"

# ── 2) مفتاح الإصدار (يُولَّد مرة واحدة، ومستبعد من git) ────────
KS="${YASSOTA_KEYSTORE:-yassota-release.jks}"
KS_PROPS="keystore.properties"
if [ -f "$KS_PROPS" ]; then
  # shellcheck disable=SC1090
  source "$KS_PROPS"
fi
KS_PASS="${KS_PASS:-${YASSOTA_KS_PASS:-}}"
KEY_ALIAS="${KEY_ALIAS:-yassota}"

if [ ! -f "$KS" ]; then
  echo "▸ لا يوجد مفتاح إصدار — سأولّد واحداً جديداً (احتفظ به للأبد: بدونه لا تستطيع نشر تحديثات)"
  [ -n "$KS_PASS" ] || KS_PASS="$(head -c 24 /dev/urandom | base64 | tr -d '/+=' | head -c 24)"
  keytool -genkeypair -v \
    -keystore "$KS" -alias "$KEY_ALIAS" \
    -keyalg RSA -keysize 4096 -validity 10000 \
    -storepass "$KS_PASS" -keypass "$KS_PASS" \
    -dname "CN=YASSOTA, OU=Mobile, O=YASSOTA, L=, ST=, C=SA" >/dev/null
  umask 077
  cat > "$KS_PROPS" <<EOF
# أسرار التوقيع — مستبعد من git عبر .gitignore. احتفظ بنسخة احتياطية آمنة!
KS_PASS=$KS_PASS
KEY_ALIAS=$KEY_ALIAS
EOF
  echo "▸ حُفظت كلمة المرور في $KS_PROPS (خارج git). خُذ نسخة احتياطية من $KS و $KS_PROPS"
fi
[ -n "$KS_PASS" ] || { echo "✗ لا أعرف كلمة مرور المفتاح. ضعها في $KS_PROPS أو متغير YASSOTA_KS_PASS"; exit 1; }

# ── 3) تنظيف ومجلدات ─────────────────────────────────────────
rm -rf "$OUT"; mkdir -p "$OUT/res" "$OUT/gen" "$OUT/classes" "$OUT/dex"

# مزامنة ثابت الإصدار مع versionName
cat > src/com/yassota/app/BuildInfo.java <<EOF
package com.yassota.app;

/** ثابت الإصدار — يولّده build.sh ليطابق versionName. */
public final class BuildInfo {
    public static final String VERSION = "$VERSION_NAME";
    private BuildInfo() { }
}
EOF

# ── 4) الموارد ───────────────────────────────────────────────
echo "▸ aapt2 compile"
"$BT/aapt2" compile --dir res -o "$OUT/res/resources.zip"

echo "▸ aapt2 link"
"$BT/aapt2" link \
  -I "$JAR" \
  --manifest AndroidManifest.xml \
  --java "$OUT/gen" \
  --min-sdk-version "$MIN_API" \
  --target-sdk-version "$TARGET_API" \
  --version-code "$VERSION_CODE" \
  --version-name "$VERSION_NAME" \
  -o "$OUT/base.apk" \
  "$OUT/res/resources.zip"

# ── 5) ترجمة جافا ────────────────────────────────────────────
echo "▸ javac"
find src "$OUT/gen" -name '*.java' > "$OUT/sources.txt"
javac --release 8 -nowarn -cp "$JAR" -d "$OUT/classes" @"$OUT/sources.txt"

# ── 6) dex ───────────────────────────────────────────────────
echo "▸ d8"
find "$OUT/classes" -name '*.class' > "$OUT/classes.txt"
"$BT/d8" --release --min-api "$MIN_API" --lib "$JAR" --output "$OUT/dex" @"$OUT/classes.txt"

# ── 7) تجميع الحزمة ──────────────────────────────────────────
echo "▸ package"
cp "$OUT/base.apk" "$OUT/unsigned.apk"
(cd "$OUT/dex" && zip -q ../unsigned.apk classes*.dex)

echo "▸ zipalign"
"$BT/zipalign" -f -p 4 "$OUT/unsigned.apk" "$OUT/aligned.apk"

# ── 8) التوقيع (Release، v2+v3) ──────────────────────────────
echo "▸ apksigner sign"
"$BT/apksigner" sign \
  --ks "$KS" --ks-key-alias "$KEY_ALIAS" \
  --ks-pass "pass:$KS_PASS" --key-pass "pass:$KS_PASS" \
  --v1-signing-enabled false --v2-signing-enabled true --v3-signing-enabled true \
  --out "$APK" "$OUT/aligned.apk"

# ── 9) التحقق واستخراج البصمة ────────────────────────────────
echo
echo "═══════════ تم البناء ═══════════"
"$BT/apksigner" verify --verbose --print-certs "$APK" | sed -n '1,12p'
SHA256="$("$BT/apksigner" verify --print-certs "$APK" | grep -i 'SHA-256 digest' | head -1 | awk '{print $NF}')"
# حوّل إلى صيغة النقطتين المطلوبة في assetlinks.json
FP="$(echo "$SHA256" | tr 'a-z' 'A-Z' | sed 's/\(..\)/\1:/g; s/:$//')"

SIZE="$(du -h "$APK" | cut -f1)"
echo
echo "الملف        : $APK"
echo "الحجم        : $SIZE"
echo "الحزمة       : $APP_ID"
echo "الإصدار      : $VERSION_NAME (code $VERSION_CODE)"
echo "أقل أندرويد  : API $MIN_API"
echo "SHA-256      : $FP"
echo
echo "▸ ضع هذه البصمة في مكانين:"
echo "  1) yassota/.well-known/assetlinks.json  (بدل REPLACE_WITH_RELEASE_SHA256_FINGERPRINT)"
echo "  2) لوحة الإدارة /admin ← تطبيق أندرويد ← SHA-256"
echo
echo '  "sha256_cert_fingerprints": ["'"$FP"'"]'
