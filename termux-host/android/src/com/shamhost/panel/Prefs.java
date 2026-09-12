package com.shamhost.panel;

import android.content.Context;
import android.content.SharedPreferences;

/** تخزين إعدادات الاتصال. */
final class Prefs {
    private static final String NAME = "shamhost";
    private static final String KEY_URL = "url";

    static SharedPreferences of(Context c) {
        return c.getSharedPreferences(NAME, Context.MODE_PRIVATE);
    }

    static String url(Context c) {
        return of(c).getString(KEY_URL, null);
    }

    static void setUrl(Context c, String url) {
        of(c).edit().putString(KEY_URL, url).apply();
    }

    static void clear(Context c) {
        of(c).edit().remove(KEY_URL).apply();
    }

    private Prefs() {}
}
