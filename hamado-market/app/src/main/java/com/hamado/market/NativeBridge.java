package com.hamado.market;

import android.content.ContentValues;
import android.content.Intent;
import android.net.Uri;
import android.os.Build;
import android.os.Environment;
import android.provider.MediaStore;
import android.webkit.JavascriptInterface;
import android.widget.Toast;

import java.io.DataOutputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.io.ByteArrayOutputStream;

/**
 * الجسر بين واجهة الويب وأندرويد: حفظ النسخ الاحتياطية، مشاركتها
 * (ومنها الرفع إلى Google Drive عبر قائمة المشاركة)، وإرسالها لتيليجرام.
 *
 * إرسال تيليجرام يتم من جافا وليس من JavaScript عمداً: طلب fetch من داخل
 * WebView يخضع لقيود CORS وقد يفشل، أما الطلب الأصلي فلا.
 */
public class NativeBridge {

    private final MainActivity act;

    public NativeBridge(MainActivity a) { this.act = a; }

    @JavascriptInterface
    public void toast(final String msg) {
        act.runOnUiThread(new Runnable() {
            @Override public void run() { Toast.makeText(act, msg, Toast.LENGTH_SHORT).show(); }
        });
    }

    @JavascriptInterface
    public void exitApp() { act.exitApp(); }

    /** يحفظ النسخة الاحتياطية في مجلد التنزيلات. */
    @JavascriptInterface
    public String saveFile(String name, String content) {
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                ContentValues cv = new ContentValues();
                cv.put(MediaStore.Downloads.DISPLAY_NAME, name);
                cv.put(MediaStore.Downloads.MIME_TYPE, "application/json");
                cv.put(MediaStore.Downloads.IS_PENDING, 1);
                Uri uri = act.getContentResolver().insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, cv);
                if (uri == null) return "ERR:no-uri";
                OutputStream os = act.getContentResolver().openOutputStream(uri);
                if (os == null) return "ERR:no-stream";
                os.write(content.getBytes("UTF-8"));
                os.close();
                cv.clear();
                cv.put(MediaStore.Downloads.IS_PENDING, 0);
                act.getContentResolver().update(uri, cv, null, null);
            } else {
                File dir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS);
                if (!dir.exists()) dir.mkdirs();
                FileOutputStream fos = new FileOutputStream(new File(dir, name));
                fos.write(content.getBytes("UTF-8"));
                fos.close();
            }
            toast("حُفظت النسخة في مجلد التنزيلات");
            return "OK";
        } catch (Exception e) {
            return "ERR:" + e.getMessage();
        }
    }

    /** يفتح قائمة المشاركة (Google Drive، واتساب، البريد…). */
    @JavascriptInterface
    public String shareFile(String name, String content) {
        try {
            File dir = new File(act.getCacheDir(), "backups");
            if (!dir.exists()) dir.mkdirs();
            File f = new File(dir, name);
            FileOutputStream fos = new FileOutputStream(f);
            fos.write(content.getBytes("UTF-8"));
            fos.close();

            Uri uri = androidx_FileProvider_getUriForFile(f);
            Intent i = new Intent(Intent.ACTION_SEND);
            i.setType("application/json");
            i.putExtra(Intent.EXTRA_STREAM, uri);
            i.putExtra(Intent.EXTRA_SUBJECT, name);
            i.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
            act.startActivity(Intent.createChooser(i, "مشاركة النسخة الاحتياطية"));
            return "OK";
        } catch (Exception e) {
            return "ERR:" + e.getMessage();
        }
    }

    private Uri androidx_FileProvider_getUriForFile(File f) {
        return HamadoFileProvider.getUriForFile(act, act.getPackageName() + ".files", f);
    }

    /** يرسل النسخة الاحتياطية كملف إلى تيليجرام عبر بوت. */
    @JavascriptInterface
    public String sendTelegram(String token, String chatId, String name, String content) {
        HttpURLConnection conn = null;
        try {
            String boundary = "----hamado" + System.currentTimeMillis();
            URL url = new URL("https://api.telegram.org/bot" + token + "/sendDocument");
            conn = (HttpURLConnection) url.openConnection();
            conn.setDoOutput(true);
            conn.setRequestMethod("POST");
            conn.setConnectTimeout(20000);
            conn.setReadTimeout(40000);
            conn.setRequestProperty("Content-Type", "multipart/form-data; boundary=" + boundary);

            DataOutputStream out = new DataOutputStream(conn.getOutputStream());
            writeField(out, boundary, "chat_id", chatId);
            writeField(out, boundary, "caption", "نسخة احتياطية — Hamado Market");

            out.writeBytes("--" + boundary + "\r\n");
            out.writeBytes("Content-Disposition: form-data; name=\"document\"; filename=\"" + name + "\"\r\n");
            out.writeBytes("Content-Type: application/json\r\n\r\n");
            out.write(content.getBytes("UTF-8"));
            out.writeBytes("\r\n--" + boundary + "--\r\n");
            out.flush();
            out.close();

            int code = conn.getResponseCode();
            InputStream is = code >= 400 ? conn.getErrorStream() : conn.getInputStream();
            String body = readAll(is);
            if (code == 200 && body.contains("\"ok\":true")) return "OK";
            return "ERR:" + code + " " + body.substring(0, Math.min(180, body.length()));
        } catch (Exception e) {
            return "ERR:" + e.getMessage();
        } finally {
            if (conn != null) conn.disconnect();
        }
    }

    private void writeField(DataOutputStream out, String boundary, String key, String val) throws Exception {
        out.writeBytes("--" + boundary + "\r\n");
        out.writeBytes("Content-Disposition: form-data; name=\"" + key + "\"\r\n");
        out.writeBytes("Content-Type: text/plain; charset=UTF-8\r\n\r\n");
        out.write(val.getBytes("UTF-8"));
        out.writeBytes("\r\n");
    }

    private String readAll(InputStream is) throws Exception {
        if (is == null) return "";
        ByteArrayOutputStream bos = new ByteArrayOutputStream();
        byte[] buf = new byte[4096];
        int n;
        while ((n = is.read(buf)) > 0) bos.write(buf, 0, n);
        is.close();
        return new String(bos.toByteArray(), "UTF-8");
    }
}
