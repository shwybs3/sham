package com.shamhost.panel;

import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;

/**
 * تشغيل أوامر ShamHost داخل Termux على نفس الجهاز عبر خدمة RunCommandService.
 * يتطلب في Termux:  allow-external-apps = true  في ~/.termux/termux.properties
 */
final class Termux {
    static final String PKG = "com.termux";
    private static final String SERVICE = "com.termux.app.RunCommandService";
    private static final String ACTION = "com.termux.RUN_COMMAND";
    private static final String BASH = "/data/data/com.termux/files/usr/bin/bash";
    private static final String HOME = "/data/data/com.termux/files/home";

    static boolean installed(Context c) {
        try {
            c.getPackageManager().getPackageInfo(PKG, 0);
            return true;
        } catch (PackageManager.NameNotFoundException e) {
            return false;
        }
    }

    /**
     * @param background true لتنفيذ صامت، false لفتح جلسة مرئية في Termux.
     * @return true إذا قُبل الطلب.
     */
    static boolean run(Context c, String command, boolean background) {
        Intent i = new Intent();
        i.setClassName(PKG, SERVICE);
        i.setAction(ACTION);
        i.putExtra("com.termux.RUN_COMMAND_PATH", BASH);
        i.putExtra("com.termux.RUN_COMMAND_ARGUMENTS", new String[] { "-lc", command });
        i.putExtra("com.termux.RUN_COMMAND_WORKDIR", HOME);
        i.putExtra("com.termux.RUN_COMMAND_BACKGROUND", background);
        i.putExtra("com.termux.RUN_COMMAND_SESSION_ACTION", "0");
        try {
            c.startService(i);
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    private Termux() {}
}
