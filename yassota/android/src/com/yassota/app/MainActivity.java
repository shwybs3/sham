package com.yassota.app;

import android.Manifest;
import android.app.Activity;
import android.app.DownloadManager;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;
import android.widget.TextView;

/**
 * الشاشة الرئيسية — غلاف WebView لموقع YASSOTA.
 * يبقى داخل النطاق الرسمي، ويفتح الروابط الخارجية في المتصفح.
 * يدعم رفع الصور (إنشاء منشور / تغيير الأفتار) والتنزيلات وروابط App Links.
 */
public class MainActivity extends Activity {

    /** النطاق الرسمي — أي رابط خارجه يُفتح في المتصفح لا داخل التطبيق. */
    static final String HOST = "yassota.com";
    static final String HOME = "https://yassota.com/";

    private WebView web;
    private ProgressBar bar;
    private View errorBox;
    private ValueCallback<Uri[]> filePathCallback;

    private static final int REQ_FILE = 1001;
    private static final int REQ_PERMS = 1002;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        web = (WebView) findViewById(R.id.web);
        bar = (ProgressBar) findViewById(R.id.bar);
        errorBox = findViewById(R.id.errorBox);

        findViewById(R.id.btnRetry).setOnClickListener(new View.OnClickListener() {
            public void onClick(View v) { errorBox.setVisibility(View.GONE); web.reload(); }
        });

        setupWebView();
        requestRuntimePermissions();

        String start = urlFromIntent(getIntent());
        if (savedInstanceState != null) web.restoreState(savedInstanceState);
        else web.loadUrl(start != null ? start : HOME);
    }

    private void setupWebView() {
        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);              // مطلوب لتفضيل المظهر (localStorage)
        s.setDatabaseEnabled(true);
        s.setLoadsImagesAutomatically(true);
        s.setJavaScriptCanOpenWindowsAutomatically(true);
        s.setSupportMultipleWindows(false);
        s.setUseWideViewPort(true);
        s.setLoadWithOverviewMode(true);
        s.setCacheMode(WebSettings.LOAD_DEFAULT);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setUserAgentString(s.getUserAgentString() + " YassotaApp/" + BuildInfo.VERSION);
        if (Build.VERSION.SDK_INT >= 21) s.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);

        CookieManager.getInstance().setAcceptCookie(true);
        if (Build.VERSION.SDK_INT >= 21) CookieManager.getInstance().setAcceptThirdPartyCookies(web, true);

        web.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView v, WebResourceRequest req) {
                return handleUrl(req.getUrl());
            }

            @Override
            @SuppressWarnings("deprecation")
            public boolean shouldOverrideUrlLoading(WebView v, String url) {
                return handleUrl(Uri.parse(url));
            }

            @Override
            public void onPageStarted(WebView v, String url, Bitmap favicon) {
                bar.setVisibility(View.VISIBLE);
            }

            @Override
            public void onPageFinished(WebView v, String url) {
                bar.setVisibility(View.GONE);
            }

            @Override
            @SuppressWarnings("deprecation")
            public void onReceivedError(WebView v, int code, String desc, String failingUrl) {
                // لا تُظهر شاشة الخطأ لأخطاء موارد فرعية
                if (failingUrl != null && failingUrl.equals(v.getUrl())) showError();
                else if (v.getUrl() == null) showError();
            }
        });

        web.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView v, int p) {
                bar.setProgress(p);
                bar.setVisibility(p >= 100 ? View.GONE : View.VISIBLE);
            }

            /** يجعل <input type="file"> يعمل — بدونه لا يمكن رفع صورة لمنشور. */
            @Override
            public boolean onShowFileChooser(WebView v, ValueCallback<Uri[]> cb, FileChooserParams params) {
                if (filePathCallback != null) filePathCallback.onReceiveValue(null);
                filePathCallback = cb;
                try {
                    Intent i = params.createIntent();
                    i.setType("image/*");
                    startActivityForResult(Intent.createChooser(i, getString(R.string.pick_image)), REQ_FILE);
                    return true;
                } catch (Exception e) {
                    filePathCallback = null;
                    return false;
                }
            }
        });

        // تنزيل الملفات عبر مدير التنزيل مع ملف تعريف الجلسة
        web.setDownloadListener(new DownloadListener() {
            public void onDownloadStart(String url, String ua, String cd, String mime, long size) {
                try {
                    DownloadManager.Request r = new DownloadManager.Request(Uri.parse(url));
                    r.addRequestHeader("Cookie", CookieManager.getInstance().getCookie(url));
                    r.addRequestHeader("User-Agent", ua);
                    r.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
                    String name = URLUtilName(url, cd, mime);
                    r.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, name);
                    DownloadManager dm = (DownloadManager) getSystemService(DOWNLOAD_SERVICE);
                    if (dm != null) dm.enqueue(r);
                } catch (Exception ignored) { }
            }
        });
    }

    private static String URLUtilName(String url, String contentDisposition, String mime) {
        try { return android.webkit.URLUtil.guessFileName(url, contentDisposition, mime); }
        catch (Exception e) { return "yassota-download"; }
    }

    /** true = تعاملنا مع الرابط بأنفسنا (خارجي)، false = دع الـWebView يحمّله. */
    private boolean handleUrl(Uri uri) {
        if (uri == null) return false;
        String host = uri.getHost();
        String scheme = uri.getScheme();
        if (host != null && (host.equals(HOST) || host.endsWith("." + HOST))) return false;
        // روابط غير http(s) مثل tel: و mailto: و intent: → للنظام
        try {
            startActivity(new Intent(Intent.ACTION_VIEW, uri));
            return true;
        } catch (Exception e) {
            return scheme != null && !scheme.startsWith("http");
        }
    }

    private void showError() {
        bar.setVisibility(View.GONE);
        errorBox.setVisibility(View.VISIBLE);
        TextView t = (TextView) findViewById(R.id.errorText);
        t.setText(getString(R.string.err_offline));
    }

    private void requestRuntimePermissions() {
        if (Build.VERSION.SDK_INT < 23) return;
        java.util.ArrayList<String> need = new java.util.ArrayList<String>();
        if (Build.VERSION.SDK_INT >= 33) {
            if (checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED)
                need.add(Manifest.permission.POST_NOTIFICATIONS);
            if (checkSelfPermission(Manifest.permission.READ_MEDIA_IMAGES) != PackageManager.PERMISSION_GRANTED)
                need.add(Manifest.permission.READ_MEDIA_IMAGES);
        } else {
            if (checkSelfPermission(Manifest.permission.READ_EXTERNAL_STORAGE) != PackageManager.PERMISSION_GRANTED)
                need.add(Manifest.permission.READ_EXTERNAL_STORAGE);
        }
        if (!need.isEmpty()) requestPermissions(need.toArray(new String[0]), REQ_PERMS);
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        String u = urlFromIntent(intent);
        if (u != null && web != null) web.loadUrl(u);
    }

    /** يستخرج رابط App Link (‎/post/… ‎/user/… إلخ) من الـIntent. */
    private String urlFromIntent(Intent intent) {
        if (intent == null) return null;
        Uri data = intent.getData();
        if (data == null) return null;
        String host = data.getHost();
        if (host != null && (host.equals(HOST) || host.endsWith("." + HOST))) return data.toString();
        return null;
    }

    @Override
    protected void onActivityResult(int req, int res, Intent data) {
        super.onActivityResult(req, res, data);
        if (req != REQ_FILE) return;
        if (filePathCallback == null) return;
        Uri[] out = null;
        if (res == RESULT_OK && data != null && data.getData() != null) out = new Uri[]{ data.getData() };
        filePathCallback.onReceiveValue(out);
        filePathCallback = null;
    }

    @Override
    public void onBackPressed() {
        if (web != null && web.canGoBack()) web.goBack();
        else super.onBackPressed();
    }

    @Override
    protected void onSaveInstanceState(Bundle out) {
        super.onSaveInstanceState(out);
        if (web != null) web.saveState(out);
    }
}
