package com.hamado.market;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.content.Intent;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.view.KeyEvent;
import android.view.View;
import android.view.WindowManager;
import android.webkit.WebResourceRequest;
import android.webkit.WebResourceResponse;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import java.io.IOException;
import java.io.InputStream;
import java.util.HashMap;
import java.util.Map;

/**
 * Hamado Market — غلاف أندرويد.
 *
 * ملاحظة معمارية مهمة: لا نحمّل الملفات بـ file:///android_asset لأن متصفح
 * كروم (وبالتالي WebView) يمنع IndexedDB على أصل file:// — وهو ما يعني
 * ضياع كل بيانات الدفتر. بدلاً من ذلك نعترض الطلبات ونخدم نفس الملفات
 * من داخل التطبيق تحت أصل https://hamado.local، فيعامله WebView كأصل آمن
 * وتعمل قاعدة البيانات المحلية بشكل موثوق.
 */
public class MainActivity extends Activity {

    private static final String ORIGIN = "https://hamado.local";
    private WebView web;

    private static final Map<String, String> MIME = new HashMap<String, String>();
    static {
        MIME.put("html", "text/html");
        MIME.put("js", "application/javascript");
        MIME.put("css", "text/css");
        MIME.put("json", "application/json");
        MIME.put("svg", "image/svg+xml");
        MIME.put("png", "image/png");
        MIME.put("jpg", "image/jpeg");
        MIME.put("woff2", "font/woff2");
    }

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        getWindow().setStatusBarColor(0xFF0A0E13);
        getWindow().setNavigationBarColor(0xFF0E141B);
        getWindow().addFlags(WindowManager.LayoutParams.FLAG_DRAWS_SYSTEM_BAR_BACKGROUNDS);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            View decor = getWindow().getDecorView();
            decor.setSystemUiVisibility(decor.getSystemUiVisibility() & ~View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR);
        }

        web = new WebView(this);
        setContentView(web);

        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setDatabaseEnabled(true);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setSupportZoom(false);
        s.setBuiltInZoomControls(false);
        s.setTextZoom(100);
        s.setCacheMode(WebSettings.LOAD_NO_CACHE);
        s.setAllowFileAccess(false);
        s.setAllowContentAccess(false);

        web.setBackgroundColor(0xFF0A0E13);
        web.setOverScrollMode(View.OVER_SCROLL_NEVER);
        web.addJavascriptInterface(new NativeBridge(this), "AndroidBridge");

        web.setWebViewClient(new WebViewClient() {
            @Override
            public WebResourceResponse shouldInterceptRequest(WebView view, WebResourceRequest request) {
                return serve(request.getUrl());
            }

            @Override
            @SuppressWarnings("deprecation")
            public WebResourceResponse shouldInterceptRequest(WebView view, String url) {
                return serve(Uri.parse(url));
            }

            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                return openExternal(request.getUrl());
            }

            @Override
            @SuppressWarnings("deprecation")
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                return openExternal(Uri.parse(url));
            }
        });

        web.loadUrl(ORIGIN + "/index.html");
    }

    /** يخدم ملفات التطبيق من assets/www تحت أصل https الوهمي. */
    private WebResourceResponse serve(Uri uri) {
        if (uri == null || !"hamado.local".equals(uri.getHost())) return null;
        String path = uri.getPath();
        if (path == null || path.equals("/")) path = "/index.html";
        if (path.contains("..")) return null;

        String asset = "www" + path;
        try {
            InputStream in = getAssets().open(asset);
            String ext = "";
            int dot = path.lastIndexOf('.');
            if (dot >= 0) ext = path.substring(dot + 1).toLowerCase();
            String mime = MIME.containsKey(ext) ? MIME.get(ext) : "application/octet-stream";
            WebResourceResponse res = new WebResourceResponse(mime, "utf-8", in);
            Map<String, String> headers = new HashMap<String, String>();
            headers.put("Cache-Control", "no-store");
            res.setResponseHeaders(headers);
            return res;
        } catch (IOException e) {
            return new WebResourceResponse("text/plain", "utf-8", 404, "Not Found",
                    new HashMap<String, String>(), null);
        }
    }

    /** الروابط الخارجية (هاتف/واتساب/مواقع) تُفتح خارج التطبيق. */
    private boolean openExternal(Uri uri) {
        if (uri == null) return false;
        if ("hamado.local".equals(uri.getHost())) return false;
        try {
            startActivity(new Intent(Intent.ACTION_VIEW, uri));
        } catch (Exception ignored) { }
        return true;
    }

    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        if (keyCode == KeyEvent.KEYCODE_BACK) {
            // نترك للتطبيق فرصة إغلاق النوافذ المنبثقة أولاً
            web.evaluateJavascript("window.dispatchEvent(new Event('hm-back'));", null);
            return true;
        }
        return super.onKeyDown(keyCode, event);
    }

    public void exitApp() {
        runOnUiThread(new Runnable() {
            @Override public void run() { finish(); }
        });
    }
}
