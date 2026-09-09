package com.shamhost.panel;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.app.DownloadManager;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.view.KeyEvent;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.URLUtil;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ImageButton;
import android.widget.PopupMenu;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

/** يعرض لوحة ShamHost داخل WebView مع دعم الرفع والتنزيل. */
public class WebActivity extends Activity {

    private static final int REQ_FILE = 1001;

    private WebView web;
    private ProgressBar progress;
    private TextView barTitle;
    private View errorBox;
    private TextView errorDetail;

    private String baseUrl;
    private ValueCallback<Uri[]> filePathCallback;
    private long lastBackPress = 0L;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle b) {
        super.onCreate(b);
        setContentView(R.layout.activity_web);

        baseUrl = getIntent() != null ? getIntent().getStringExtra("url") : null;
        if (baseUrl == null) baseUrl = Prefs.url(this);
        if (baseUrl == null) {
            backToSetup();
            return;
        }

        web = findViewById(R.id.web);
        progress = findViewById(R.id.progress);
        barTitle = findViewById(R.id.barTitle);
        errorBox = findViewById(R.id.errorBox);
        errorDetail = findViewById(R.id.errorDetail);

        barTitle.setText(baseUrl);

        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setDatabaseEnabled(true);
        s.setLoadWithOverviewMode(true);
        s.setUseWideViewPort(true);
        s.setBuiltInZoomControls(true);
        s.setDisplayZoomControls(false);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);
        s.setCacheMode(WebSettings.LOAD_DEFAULT);

        CookieManager.getInstance().setAcceptCookie(true);
        CookieManager.getInstance().setAcceptThirdPartyCookies(web, true);

        web.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView v, WebResourceRequest r) {
                Uri u = r.getUrl();
                String scheme = u.getScheme();
                if (scheme != null && !scheme.startsWith("http")) {
                    try {
                        startActivity(new Intent(Intent.ACTION_VIEW, u));
                    } catch (Exception ignored) {}
                    return true;
                }
                return false;
            }

            @Override
            public void onPageFinished(WebView v, String url) {
                progress.setVisibility(View.GONE);
                barTitle.setText(url);
            }

            @Override
            public void onReceivedError(WebView v, WebResourceRequest req, WebResourceError err) {
                if (req != null && !req.isForMainFrame()) return;
                showError(err != null ? String.valueOf(err.getDescription()) : null);
            }
        });

        web.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView v, int p) {
                progress.setVisibility(p >= 100 ? View.GONE : View.VISIBLE);
                progress.setProgress(p);
            }

            @Override
            public boolean onShowFileChooser(WebView v, ValueCallback<Uri[]> cb,
                                             FileChooserParams params) {
                if (filePathCallback != null) filePathCallback.onReceiveValue(null);
                filePathCallback = cb;
                try {
                    Intent i = new Intent(Intent.ACTION_GET_CONTENT);
                    i.addCategory(Intent.CATEGORY_OPENABLE);
                    i.setType("*/*");
                    startActivityForResult(Intent.createChooser(i, "اختر ملفاً"), REQ_FILE);
                    return true;
                } catch (Exception e) {
                    filePathCallback = null;
                    return false;
                }
            }
        });

        web.setDownloadListener((url, agent, disposition, mime, size) -> {
            try {
                DownloadManager.Request r = new DownloadManager.Request(Uri.parse(url));
                String name = URLUtil.guessFileName(url, disposition, mime);
                r.addRequestHeader("Cookie", CookieManager.getInstance().getCookie(url));
                r.setTitle(name);
                r.setNotificationVisibility(
                        DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
                r.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, name);
                DownloadManager dm = (DownloadManager) getSystemService(DOWNLOAD_SERVICE);
                if (dm != null) dm.enqueue(r);
                Toast.makeText(this, "جارٍ التنزيل: " + name, Toast.LENGTH_SHORT).show();
            } catch (Exception e) {
                Toast.makeText(this, "تعذّر التنزيل", Toast.LENGTH_SHORT).show();
            }
        });

        findViewById(R.id.btnReload).setOnClickListener(v -> load());
        findViewById(R.id.btnRetry).setOnClickListener(v -> load());
        findViewById(R.id.btnBackSetup).setOnClickListener(v -> backToSetup());
        findViewById(R.id.btnMenu).setOnClickListener(this::showMenu);

        load();
    }

    private void load() {
        errorBox.setVisibility(View.GONE);
        web.setVisibility(View.VISIBLE);
        web.loadUrl(baseUrl);
    }

    private void showError(String detail) {
        web.setVisibility(View.GONE);
        errorBox.setVisibility(View.VISIBLE);
        String hint = getString(R.string.load_error_hint);
        errorDetail.setText(detail == null ? hint : hint + "\n\n" + detail);
    }

    private void showMenu(View anchor) {
        PopupMenu m = new PopupMenu(this, anchor);
        m.getMenu().add(0, 1, 0, R.string.refresh);
        m.getMenu().add(0, 2, 1, R.string.change_server);
        if (Termux.installed(this)) m.getMenu().add(0, 3, 2, R.string.termux_control);
        m.setOnMenuItemClickListener(item -> {
            switch (item.getItemId()) {
                case 1: load(); return true;
                case 2: backToSetup(); return true;
                case 3: startActivity(new Intent(this, ControlActivity.class)); return true;
                default: return false;
            }
        });
        m.show();
    }

    private void backToSetup() {
        Intent i = new Intent(this, SetupActivity.class);
        i.putExtra("edit", true);
        i.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
        startActivity(i);
        finish();
    }

    @Override
    protected void onActivityResult(int req, int res, Intent data) {
        if (req != REQ_FILE) {
            super.onActivityResult(req, res, data);
            return;
        }
        if (filePathCallback == null) return;
        Uri[] out = null;
        if (res == RESULT_OK && data != null && data.getData() != null) {
            out = new Uri[] { data.getData() };
        }
        filePathCallback.onReceiveValue(out);
        filePathCallback = null;
    }

    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        if (keyCode == KeyEvent.KEYCODE_BACK && web != null && web.canGoBack()
                && errorBox.getVisibility() != View.VISIBLE) {
            web.goBack();
            return true;
        }
        if (keyCode == KeyEvent.KEYCODE_BACK) {
            long now = System.currentTimeMillis();
            if (now - lastBackPress < 2000) return super.onKeyDown(keyCode, event);
            lastBackPress = now;
            Toast.makeText(this, R.string.exit_confirm, Toast.LENGTH_SHORT).show();
            return true;
        }
        return super.onKeyDown(keyCode, event);
    }

    @Override
    protected void onPause() {
        super.onPause();
        if (web != null) web.onPause();
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (web != null) web.onResume();
    }

    @Override
    protected void onDestroy() {
        if (web != null) web.destroy();
        super.onDestroy();
    }
}
