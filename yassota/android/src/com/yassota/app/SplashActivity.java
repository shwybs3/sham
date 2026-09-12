package com.yassota.app;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;

/** شاشة بداية قصيرة تمرّر رابط App Link إن وُجد إلى الشاشة الرئيسية. */
public class SplashActivity extends Activity {
    @Override
    protected void onCreate(Bundle b) {
        super.onCreate(b);
        Intent i = new Intent(this, MainActivity.class);
        if (getIntent() != null && getIntent().getData() != null) {
            i.setAction(Intent.ACTION_VIEW);
            i.setData(getIntent().getData());
        }
        startActivity(i);
        finish();
        overridePendingTransition(0, 0);
    }
}
