#!/usr/bin/env bash
# بناء ShamHost.apk بدون Gradle — يحتاج فقط JDK + Android SDK (build-tools + platform).
set -euo pipefail

SDK="${ANDROID_HOME:-${ANDROID_SDK_ROOT:-$HOME/android-sdk}}"
BT_VER="${BUILD_TOOLS:-35.0.0}"
API="${TARGET_API:-34}"
MIN_API="${MIN_API:-24}"
VERSION_CODE="${VERSION_CODE:-1}"
VERSION_NAME="${VERSION_NAME:-1.0.0}"

BT="$SDK/build-tools/$BT_VER"
JAR="$SDK/platforms/android-$API/android.jar"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT="$HERE/build"

for f in "$BT/aapt2" "$BT/d8" "$BT/zipalign" "$BT/apksigner" "$JAR"; do
  [ -e "$f" ] || { echo "مفقود: $f"; echo "ثبّت: sdkmanager \"build-tools;$BT_VER\" \"platforms;android-$API\""; exit 1; }
done

# d8 في build-tools 34 وما دونه يتعطل على الفئات المجهولة عند التشغيل بـ JDK 21،
# لذلك 35.0.0 هو الحد الأدنى الموصى به هنا.

rm -rf "$OUT"
mkdir -p "$OUT"/{res,gen,classes,dex,apk}

echo "==> ترجمة الموارد"
"$BT/aapt2" compile --dir "$HERE/res" -o "$OUT/res/resources.zip"

echo "==> ربط الموارد وتوليد R.java"
"$BT/aapt2" link \
  -o "$OUT/apk/base.apk" \
  -I "$JAR" \
  --manifest "$HERE/AndroidManifest.xml" \
  --java "$OUT/gen" \
  --min-sdk-version "$MIN_API" \
  --target-sdk-version "$API" \
  --version-code "$VERSION_CODE" \
  --version-name "$VERSION_NAME" \
  --auto-add-overlay \
  "$OUT/res/resources.zip"

echo "==> ترجمة Java"
find "$HERE/src" "$OUT/gen" -name '*.java' > "$OUT/sources.txt"
javac -nowarn -Xlint:-options \
  --release 8 -cp "$JAR" \
  -d "$OUT/classes" @"$OUT/sources.txt"

echo "==> توليد dex"
find "$OUT/classes" -name '*.class' > "$OUT/classes.txt"
"$BT/d8" --lib "$JAR" --min-api "$MIN_API" --output "$OUT/dex" @"$OUT/classes.txt"

echo "==> تجميع الحزمة"
cp "$OUT/apk/base.apk" "$OUT/apk/unsigned.apk"
( cd "$OUT/dex" && zip -q -X "$OUT/apk/unsigned.apk" classes.dex )

KS="${KEYSTORE:-$HERE/shamhost.keystore}"
KS_PASS="${KEYSTORE_PASS:-shamhost}"
if [ ! -f "$KS" ]; then
  echo "==> توليد مفتاح توقيع"
  keytool -genkeypair -v -keystore "$KS" -alias shamhost \
    -keyalg RSA -keysize 2048 -validity 10950 \
    -storepass "$KS_PASS" -keypass "$KS_PASS" \
    -dname "CN=ShamHost, OU=Termux Hosting, O=ShamHost, C=SY" >/dev/null 2>&1
fi

echo "==> محاذاة وتوقيع"
"$BT/zipalign" -f -p 4 "$OUT/apk/unsigned.apk" "$OUT/apk/aligned.apk"
"$BT/apksigner" sign \
  --ks "$KS" --ks-pass "pass:$KS_PASS" --key-pass "pass:$KS_PASS" \
  --v1-signing-enabled true --v2-signing-enabled true --v3-signing-enabled true \
  --out "$OUT/ShamHost.apk" "$OUT/apk/aligned.apk"

"$BT/apksigner" verify --print-certs "$OUT/ShamHost.apk" >/dev/null

SIZE=$(du -h "$OUT/ShamHost.apk" | cut -f1)
echo
echo "✔ تم البناء: $OUT/ShamHost.apk ($SIZE)"
