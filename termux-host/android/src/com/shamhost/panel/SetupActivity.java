package com.shamhost.panel;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.ClipData;
import android.content.ClipboardManager;
import android.content.Context;
import android.content.Intent;
import android.net.Uri;
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

/** شاشة الإعداد: الأذونات، حالة الخدمة، التثبيت الذاتي، والاتصال باللوحة. */
public class SetupActivity extends Activity {

    private static final int REQ_PERMS = 2001;
    private static final String FDROID_TERMUX = "https://f-droid.org/packages/com.termux/";

    private EditText host, port;
    private TextView status, statusChip;
    private Button btnDiscover, btnInstall, btnStart, btnFdroid, btnTermux;
    private final Handler ui = new Handler(Looper.getMainLooper());
    private boolean askedBattery = false;

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
        statusChip = findViewById(R.id.statusChip);
        btnDiscover = findViewById(R.id.btnDiscover);
        btnInstall = findViewById(R.id.btnInstall);
        btnStart = findViewById(R.id.btnStart);
        btnFdroid = findViewById(R.id.btnFdroid);
        btnTermux = findViewById(R.id.btnTermux);

        if (saved != null) prefill(saved);
        else host.setText(Termux.installed(this) ? "127.0.0.1" : "");

        findViewById(R.id.btnConnect).setOnClickListener(v -> connect());
        btnDiscover.setOnClickListener(v -> discover());
        findViewById(R.id.btnLocal).setOnClickListener(v -> {
            host.setText("127.0.0.1");
            port.setText("8088");
            probe();
        });
        findViewById(R.id.btnCopyCmd).setOnClickListener(v -> copyCommand());
        btnInstall.setOnClickListener(v -> install());
        btnStart.setOnClickListener(v -> startServices());
        btnFdroid.setOnClickListener(v -> openUrl(FDROID_TERMUX));
        btnTermux.setOnClickListener(v -> startActivity(new Intent(this, ControlActivity.class)));

        paintTermuxState();
        // كل الأذونات تُطلب فوراً عند فتح التطبيق، في حوار واحد.
        if (!Perms.requestAll(this, REQ_PERMS)) promptBattery();
        probe();
    }

    // ───────────────────────── الأذونات ─────────────────────────

    @Override
    public void onRequestPermissionsResult(int code, String[] perms, int[] results) {
        if (code == REQ_PERMS) promptBattery();
        else super.onRequestPermissionsResult(code, perms, results);
    }

    private void promptBattery() {
        if (askedBattery || Perms.batteryExempt(this)) return;
        askedBattery = true;
        new AlertDialog.Builder(this)
                .setMessage(R.string.battery_prompt)
                .setPositiveButton(android.R.string.ok, (d, w) -> Perms.requestBatteryExemption(this))
                .setNegativeButton(android.R.string.cancel, null)
                .show();
    }

    // ───────────────────────── الحالة والتثبيت ─────────────────────────

    private void paintTermuxState() {
        boolean hasTermux = Termux.installed(this);
        btnInstall.setVisibility(hasTermux ? View.VISIBLE : View.GONE);
        btnStart.setVisibility(hasTermux ? View.VISIBLE : View.GONE);
        btnTermux.setVisibility(hasTermux ? View.VISIBLE : View.GONE);
        btnFdroid.setVisibility(hasTermux ? View.GONE : View.VISIBLE);

        String hint = getString(R.string.setup_hint) + "\n\n" + getString(R.string.perm_note);
        if (!hasTermux) hint = getString(R.string.termux_not_installed) + "\n\n" + hint;
        String ip = Discovery.localIp();
        if (ip != null) hint += "\n\nIP: " + ip;
        status.setText(hint);
    }

    /** يفحص إن كانت اللوحة تستجيب فعلاً، فيرى المستخدم السبب قبل أن يصطدم بخطأ اتصال. */
    private void probe() {
        final String h = hostValue();
        final int p = portValue();
        if (TextUtils.isEmpty(h)) {
            statusChip.setText(R.string.status_checking);
            return;
        }
        final String label = h + ":" + p;
        statusChip.setText(R.string.status_checking);
        statusChip.setTextColor(getColor(R.color.muted));

        new Thread(() -> {
            final boolean up = Discovery.isPanel(h, p, 900);
            ui.post(() -> {
                statusChip.setText(getString(up ? R.string.status_up : R.string.status_down, label));
                statusChip.setTextColor(getColor(up ? R.color.ok : R.color.bad));
                if (!up && Termux.installed(this)) {
                    status.setText(getString(R.string.not_installed_hint) + "\n\n" + getString(R.string.perm_note));
                }
            });
        }, "shamhost-probe").start();
    }

    private void install() {
        if (!Termux.installed(this)) {
            openUrl(FDROID_TERMUX);
            return;
        }
        if (!Perms.granted(this, Perms.TERMUX_RUN_COMMAND)) {
            requestPermissions(new String[] { Perms.TERMUX_RUN_COMMAND }, REQ_PERMS);
            return;
        }
        boolean sent = Termux.run(this, Termux.INSTALL_COMMAND, false);
        Toast.makeText(this, sent ? R.string.install_started : R.string.cmd_failed,
                Toast.LENGTH_LONG).show();
        if (!sent) copyCommand();
    }

    private void startServices() {
        boolean sent = Termux.run(this, "shamhost start", false);
        Toast.makeText(this, sent ? R.string.cmd_sent : R.string.cmd_failed, Toast.LENGTH_LONG).show();
    }

    private void copyCommand() {
        ClipboardManager cm = (ClipboardManager) getSystemService(Context.CLIPBOARD_SERVICE);
        if (cm != null) cm.setPrimaryClip(ClipData.newPlainText("ShamHost", Termux.INSTALL_COMMAND));
        Toast.makeText(this, R.string.copied, Toast.LENGTH_LONG).show();
    }

    private void openUrl(String url) {
        try {
            startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(url)));
        } catch (Exception e) {
            Toast.makeText(this, url, Toast.LENGTH_LONG).show();
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (statusChip != null) {
            paintTermuxState();
            probe();
        }
    }

    // ───────────────────────── الاتصال ─────────────────────────

    private void prefill(String url) {
        try {
            java.net.URL u = new java.net.URL(url);
            host.setText(u.getHost());
            port.setText(String.valueOf(u.getPort() > 0 ? u.getPort() : 8088));
        } catch (Exception ignored) {}
    }

    private String hostValue() {
        return host.getText().toString().trim()
                .replaceFirst("^https?://", "")
                .replaceAll("/.*$", "");
    }

    private int portValue() {
        try {
            return Integer.parseInt(port.getText().toString().trim());
        } catch (Exception e) {
            return 8088;
        }
    }

    private void connect() {
        String h = hostValue();
        if (TextUtils.isEmpty(h)) {
            Toast.makeText(this, R.string.bad_host, Toast.LENGTH_SHORT).show();
            return;
        }
        String url = "http://" + h + ":" + portValue();
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
        final int scanPort = portValue();
        btnDiscover.setEnabled(false);
        statusChip.setText(R.string.scanning);

        new Thread(() -> {
            final List<String> hits = Discovery.scan(scanPort, 250);
            ui.post(() -> {
                btnDiscover.setEnabled(true);
                if (hits.isEmpty()) {
                    statusChip.setText(R.string.none_found);
                    statusChip.setTextColor(getColor(R.color.bad));
                    return;
                }
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
