package com.shamhost.panel;

import android.Manifest;
import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.PowerManager;
import android.provider.Settings;

import java.util.ArrayList;
import java.util.List;

/**
 * أذونات التشغيل. أندرويد لا يمنح "كل الصلاحيات" بضغطة واحدة — كل إذن خطير
 * يُطلب على حدة، والاستثناء من البطارية شاشة نظام منفصلة. هذا الصف يجمعها
 * في طلب واحد عند فتح التطبيق.
 */
final class Perms {

    /** إذن Termux للأوامر الخارجية — يعرّفه Termux كإذن خطير، فيجب طلبه وقت التشغيل. */
    static final String TERMUX_RUN_COMMAND = "com.termux.permission.RUN_COMMAND";

    static boolean granted(Context c, String perm) {
        return c.checkSelfPermission(perm) == PackageManager.PERMISSION_GRANTED;
    }

    /** الأذونات التي يحتاجها التطبيق فعلاً على هذه النسخة من أندرويد وليست ممنوحة بعد. */
    static String[] missing(Context c) {
        List<String> need = new ArrayList<>();

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            need.add(Manifest.permission.POST_NOTIFICATIONS);
        }
        if (Build.VERSION.SDK_INT <= 32) {
            need.add(Manifest.permission.READ_EXTERNAL_STORAGE);
        }
        if (Build.VERSION.SDK_INT <= 28) {
            need.add(Manifest.permission.WRITE_EXTERNAL_STORAGE);
        }
        // لا معنى لطلبه إن لم يكن Termux مثبّتاً، فهو من تعريفه.
        if (Termux.installed(c)) {
            need.add(TERMUX_RUN_COMMAND);
        }

        List<String> out = new ArrayList<>();
        for (String p : need) {
            if (!granted(c, p)) out.add(p);
        }
        return out.toArray(new String[0]);
    }

    /** يطلب كل الناقص في حوار واحد. يعيد false إن لم يكن هناك ما يُطلب. */
    static boolean requestAll(Activity a, int requestCode) {
        String[] m = missing(a);
        if (m.length == 0) return false;
        a.requestPermissions(m, requestCode);
        return true;
    }

    static boolean batteryExempt(Context c) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.M) return true;
        PowerManager pm = (PowerManager) c.getSystemService(Context.POWER_SERVICE);
        return pm == null || pm.isIgnoringBatteryOptimizations(c.getPackageName());
    }

    /**
     * يفتح شاشة النظام لاستثناء التطبيق من تحسين البطارية.
     * بدون هذا الاستثناء يقتل أندرويد الخدمات الخلفية، وهو أكثر سبب لتوقف الاستضافة.
     */
    static boolean requestBatteryExemption(Activity a) {
        if (batteryExempt(a)) return false;
        try {
            Intent i = new Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS);
            i.setData(Uri.parse("package:" + a.getPackageName()));
            a.startActivity(i);
            return true;
        } catch (Exception e) {
            try {
                a.startActivity(new Intent(Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS));
                return true;
            } catch (Exception ignored) {
                return false;
            }
        }
    }

    private Perms() {}
}
