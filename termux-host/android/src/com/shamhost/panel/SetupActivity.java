package com.shamhost.panel;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.text.TextUtils;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import java.util.List;

/** شاشة ضبط عنوان اللوحة. */
public class SetupActivity extends Activity {

    private EditText host, port;
    private TextView status;
    private Button btnDiscover;
    private final Handler ui = new Handler(Looper.getMainLooper());

    @Override
    protected void onCreate(Bundle b) {
        super.onCreate(b);

        String saved = Prefs.url(this);
        boolean edit = getIntent() != null && getIntent().getBooleanExtra("edit", false);
        if (saved != null && !edit) {
            openPanel(saved);
            return;
        }

        setContentView(R.layout.activity_setup);
        host = findViewById(R.id.host);
        port = findViewById(R.id.port);
        status = findViewById(R.id.status);
        btnDiscover = findViewById(R.id.btnDiscover);

        if (saved != null) prefill(saved);

        findViewById(R.id.btnConnect).setOnClickListener(v -> connect());
        btnDiscover.setOnClickListener(v -> discover());
        findViewById(R.id.btnLocal).setOnClickListener(v -> {
            host.setText("127.0.0.1");
            port.setText("8088");
        });

        Button termux = findViewById(R.id.btnTermux);
        if (Termux.installed(this)) {
            termux.setVisibility(View.VISIBLE);
            termux.setOnClickListener(v -> startActivity(new Intent(this, ControlActivity.class)));
        }

        String ip = Discovery.localIp();
        if (ip != null) {
            status.setText(getString(R.string.setup_hint) + "\n\nIP: " + ip);
        }
    }

    private void prefill(String url) {
        try {
            java.net.URL u = new java.net.URL(url);
            host.setText(u.getHost());
            port.setText(String.valueOf(u.getPort() > 0 ? u.getPort() : 8088));
        } catch (Exception ignored) {}
    }

    private String buildUrl() {
        String h = host.getText().toString().trim()
                .replaceFirst("^https?://", "")
                .replaceAll("/.*$", "");
        String p = port.getText().toString().trim();
        if (TextUtils.isEmpty(h)) return null;
        if (TextUtils.isEmpty(p)) p = "8088";
        return "http://" + h + ":" + p;
    }

    private void connect() {
        String url = buildUrl();
        if (url == null) {
            Toast.makeText(this, R.string.bad_host, Toast.LENGTH_SHORT).show();
            return;
        }
        Prefs.setUrl(this, url);
        openPanel(url);
    }

    private void openPanel(String url) {
        Intent i = new Intent(this, WebActivity.class);
        i.putExtra("url", url);
        startActivity(i);
        finish();
    }

    private void discover() {
        int p;
        try {
            p = Integer.parseInt(port.getText().toString().trim());
        } catch (Exception e) {
            p = 8088;
        }
        final int scanPort = p;
        btnDiscover.setEnabled(false);
        status.setText(R.string.scanning);

        new Thread(() -> {
            final List<String> hits = Discovery.scan(scanPort, 250);
            ui.post(() -> {
                btnDiscover.setEnabled(true);
                if (hits.isEmpty()) {
                    status.setText(R.string.none_found);
                    return;
                }
                status.setText("");
                final String[] items = hits.toArray(new String[0]);
                new AlertDialog.Builder(this)
                        .setTitle(R.string.pick_server)
                        .setItems(items, (d, which) -> {
                            host.setText(items[which]);
                            port.setText(String.valueOf(scanPort));
                            connect();
                        })
                        .show();
            });
        }, "shamhost-scan").start();
    }
}
