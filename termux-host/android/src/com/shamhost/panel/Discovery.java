package com.shamhost.panel;

import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.Inet4Address;
import java.net.InetAddress;
import java.net.InetSocketAddress;
import java.net.InterfaceAddress;
import java.net.NetworkInterface;
import java.net.Socket;
import java.net.URL;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Enumeration;
import java.util.List;
import java.util.concurrent.CopyOnWriteArrayList;
import java.util.concurrent.CountDownLatch;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.TimeUnit;

/** يبحث عن خوادم ShamHost على الشبكة المحلية. */
final class Discovery {

    /** عنوان IPv4 المحلي لهذا الجهاز، أو null. */
    static String localIp() {
        try {
            Enumeration<NetworkInterface> ifs = NetworkInterface.getNetworkInterfaces();
            while (ifs != null && ifs.hasMoreElements()) {
                NetworkInterface ni = ifs.nextElement();
                if (!ni.isUp() || ni.isLoopback()) continue;
                for (InterfaceAddress ia : ni.getInterfaceAddresses()) {
                    InetAddress a = ia.getAddress();
                    if (a instanceof Inet4Address && !a.isLoopbackAddress()) {
                        return a.getHostAddress();
                    }
                }
            }
        } catch (Exception ignored) {}
        return null;
    }

    /**
     * يفحص نطاق /24 الحالي بحثاً عن اللوحة على المنفذ المحدد.
     * يعمل على خيط خلفي؛ لا يُستدعى من الخيط الرئيسي.
     */
    static List<String> scan(int port, int perHostTimeoutMs) {
        List<String> found = new CopyOnWriteArrayList<>();
        String ip = localIp();
        if (ip == null) return found;
        int lastDot = ip.lastIndexOf('.');
        if (lastDot < 0) return found;
        final String prefix = ip.substring(0, lastDot + 1);

        ExecutorService pool = Executors.newFixedThreadPool(64);
        CountDownLatch done = new CountDownLatch(254);
        for (int i = 1; i <= 254; i++) {
            final String host = prefix + i;
            pool.execute(() -> {
                try {
                    if (isPanel(host, port, perHostTimeoutMs)) found.add(host);
                } catch (Exception ignored) {
                } finally {
                    done.countDown();
                }
            });
        }
        try {
            done.await(25, TimeUnit.SECONDS);
        } catch (InterruptedException ignored) {
            Thread.currentThread().interrupt();
        }
        pool.shutdownNow();
        List<String> out = new ArrayList<>(found);
        Collections.sort(out);
        return out;
    }

    /** اتصال TCP سريع ثم تحقق أن الرد فعلاً من لوحة ShamHost. */
    static boolean isPanel(String host, int port, int timeoutMs) {
        try (Socket s = new Socket()) {
            s.connect(new InetSocketAddress(host, port), timeoutMs);
        } catch (Exception e) {
            return false;
        }
        HttpURLConnection conn = null;
        try {
            conn = (HttpURLConnection) new URL("http://" + host + ":" + port + "/").openConnection();
            conn.setConnectTimeout(timeoutMs * 3);
            conn.setReadTimeout(timeoutMs * 3);
            conn.setRequestMethod("GET");
            try (InputStream in = conn.getInputStream()) {
                byte[] buf = new byte[4096];
                int n = in.read(buf);
                if (n <= 0) return false;
                return new String(buf, 0, n, "UTF-8").contains("ShamHost");
            }
        } catch (Exception e) {
            return false;
        } finally {
            if (conn != null) conn.disconnect();
        }
    }

    private Discovery() {}
}
