package com.shamhost.panel;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

/** تشغيل وإيقاف خدمات ShamHost داخل Termux على نفس الجهاز. */
public class ControlActivity extends Activity {

    /** الاسم المعروض، الأمر، هل يُنفَّذ صامتاً */
    private static final String[][] ACTIONS = {
            { "تشغيل الخدمات",        "shamhost start",    "false" },
            { "إيقاف الخدمات",        "shamhost stop",     "false" },
            { "إعادة التشغيل",        "shamhost restart",  "false" },
            { "عرض الحالة",           "shamhost status",   "false" },
            { "فحص شامل (doctor)",    "shamhost doctor",   "false" },
            { "كلمة مرور جديدة",      "shamhost password", "false" },
            { "طباعة رابط اللوحة",    "shamhost url",      "false" },
            { "قفل الاستيقاظ",        "termux-wake-lock",  "true"  },
    };

    @Override
    protected void onCreate(Bundle b) {
        super.onCreate(b);
        setContentView(R.layout.activity_control);

        LinearLayout box = findViewById(R.id.buttons);
        TextView note = findViewById(R.id.note);

        if (!Termux.installed(this)) {
            note.setText(R.string.termux_missing);
            return;
        }

        for (String[] a : ACTIONS) {
            Button btn = new Button(this, null, 0, R.style.Btn_Ghost);
            btn.setText(a[0]);
            LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT);
            lp.topMargin = dp(10);
            btn.setLayoutParams(lp);
            final String cmd = a[1];
            final boolean background = Boolean.parseBoolean(a[2]);
            btn.setOnClickListener(v -> send(cmd, background));
            box.addView(btn);
        }

        Button open = new Button(this, null, 0, R.style.Btn);
        open.setText(R.string.open_panel);
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT);
        lp.topMargin = dp(18);
        open.setLayoutParams(lp);
        open.setOnClickListener(v -> {
            String url = Prefs.url(this);
            if (url == null) url = "http://127.0.0.1:8088";
            Intent i = new Intent(this, WebActivity.class);
            i.putExtra("url", url);
            startActivity(i);
        });
        box.addView(open);
    }

    private void send(String cmd, boolean background) {
        boolean ok = Termux.run(this, cmd, background);
        Toast.makeText(this, ok ? R.string.cmd_sent : R.string.cmd_failed, Toast.LENGTH_LONG).show();
    }

    private int dp(int v) {
        return Math.round(v * getResources().getDisplayMetrics().density);
    }
}
