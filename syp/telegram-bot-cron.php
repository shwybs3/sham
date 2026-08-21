<?php
/**
 * syp/telegram-bot-cron.php — Telegram currency alert bot.
 *
 * Run every 5 minutes via cPanel Cron Jobs:
 *   *\/5 * * * * php /home/user/public_html/syp/telegram-bot-cron.php >> /dev/null 2>&1
 *
 * Features:
 * - Hourly: sends all changed pairs (≥1 unit change in rate) to all subscribers
 * - Every 3 hours: sends comprehensive summary of all pairs
 * - Stores last known rates in /tmp/syp_rates_cache.json
 * - Subscribers list in syp_config.php (or env vars)
 */
define('SYP_DATA', true);

/* ── Config ────────────────────────────────────────────────────── */
$cfgFile = __DIR__ . '/syp_config.php';
if (file_exists($cfgFile)) require $cfgFile;

$BOT_TOKEN   = defined('SYP_TG_TOKEN')    ? SYP_TG_TOKEN    : (getenv('SYP_TG_TOKEN')    ?: '');
$CHANNEL_IDS = defined('SYP_TG_CHANNELS') ? SYP_TG_CHANNELS : (getenv('SYP_TG_CHANNELS') ?: '');

if (!$BOT_TOKEN) {
    echo "No Telegram bot token configured. Set SYP_TG_TOKEN in syp/syp_config.php\n";
    exit(0);
}

/* Channel IDs: comma-separated string → array */
$channels = array_filter(array_map('trim', explode(',', $CHANNEL_IDS)));
if (!$channels) {
    echo "No channels configured. Set SYP_TG_CHANNELS in syp/syp_config.php\n";
    exit(0);
}

$pairs     = require __DIR__ . '/data.php';
$cacheFile = sys_get_temp_dir() . '/syp_rates_cache.json';
$stateFile = sys_get_temp_dir() . '/syp_bot_state.json';

$now = time();

/* ── Load state ─────────────────────────────────────────────────── */
$state = file_exists($stateFile) ? (json_decode(file_get_contents($stateFile), true) ?: []) : [];
$lastHourly  = (int)($state['last_hourly']  ?? 0);
$lastSummary = (int)($state['last_summary'] ?? 0);

/* ── Load previous rates ────────────────────────────────────────── */
$prevRates = file_exists($cacheFile) ? (json_decode(file_get_contents($cacheFile), true) ?: []) : [];

/* ── Build current rates ────────────────────────────────────────── */
$currentRates = [];
foreach ($pairs as $slug => $p) {
    $currentRates[$slug] = (float)($p['rate_inv'] ?? 0);
}

/* ── Helper: send Telegram message ─────────────────────────────── */
function tg_send(string $token, string $chatId, string $text): bool {
    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'chat_id'                  => $chatId,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => 'true',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch);
    $ok   = !curl_error($ch);
    curl_close($ch);
    return $ok && !empty(json_decode($resp, true)['ok']);
}

function broadcast(string $token, array $channels, string $text): void {
    foreach ($channels as $cid) {
        tg_send($token, $cid, $text);
        usleep(500000); // 0.5s between sends to avoid Telegram rate limit
    }
}

function fmt_rate(float $r, string $from, string $to): string {
    if ($r >= 1000)      $disp = number_format($r, 0, '.', ',');
    elseif ($r >= 10)    $disp = number_format($r, 2);
    elseif ($r >= 1)     $disp = number_format($r, 3);
    else                 $disp = number_format($r, 4);
    return "1 {$from} = {$disp} {$to}";
}

/* ── Check for changed pairs (hourly) ──────────────────────────── */
$changed = [];
if ($prevRates) {
    foreach ($currentRates as $slug => $rate) {
        $prev = $prevRates[$slug] ?? null;
        if ($prev === null) continue;
        // Detect change ≥ 1 unit OR ≥ 0.5% relative change
        $absDiff = abs($rate - $prev);
        $relDiff = $prev > 0 ? ($absDiff / $prev) : 0;
        if ($absDiff >= 1 || $relDiff >= 0.005) {
            $changed[$slug] = ['prev' => $prev, 'curr' => $rate, 'diff' => $rate - $prev];
        }
    }
}

/* ── Hourly update (changed pairs) ─────────────────────────────── */
if (($now - $lastHourly) >= 3600 && $changed) {
    $lines = ["📊 <b>تغيرات أسعار العملات</b> — " . date('d/m/Y H:i') . " (UTC)\n"];
    foreach ($changed as $slug => $c) {
        $p   = $pairs[$slug];
        $arr = $c['diff'] > 0 ? '🔺' : '🔻';
        $lines[] = $arr . ' ' . $p['flag_from'] . $p['flag_to']
            . ' <b>' . $p['from'] . '/' . $p['to'] . '</b>: '
            . fmt_rate($c['curr'], $p['from'], $p['to'])
            . ' (كان: ' . (
                $c['prev'] >= 1000 ? number_format($c['prev'],0,'.',',')
                : ($c['prev'] >= 10 ? number_format($c['prev'],2) : number_format($c['prev'],4))
              ) . ')';
    }
    $lines[] = "\n🌐 <a href=\"https://syp.yassota.com\">syp.yassota.com</a> — أسعار مرجعية للاطلاع فقط";
    $msg = implode("\n", $lines);
    broadcast($BOT_TOKEN, $channels, $msg);
    $state['last_hourly'] = $now;
    echo "Sent hourly update: " . count($changed) . " changed pairs\n";
} elseif (($now - $lastHourly) >= 3600 && !$changed) {
    /* No changes — still update timestamp to avoid spam */
    $state['last_hourly'] = $now;
    echo "Hourly check: no significant changes\n";
}

/* ── Every-3-hours comprehensive summary ───────────────────────── */
if (($now - $lastSummary) >= 10800) {
    $lines = [
        "📋 <b>ملخص أسعار العملات الشامل</b>",
        date('d/m/Y H:i') . " UTC\n",
    ];

    /* Group by category */
    $groups = [
        '🇸🇾 الليرة السورية'  => ['syp-usd','syp-eur','syp-sar','syp-aed'],
        '🇸🇦 الريال السعودي'  => ['sar-usd','sar-aed','sar-egp','sar-kwd'],
        '🇦🇪 الدرهم الإماراتي' => ['aed-usd','aed-sar','aed-egp'],
        '🇪🇬 الجنيه المصري'   => ['egp-usd','egp-sar','egp-aed'],
        '🇰🇼 الدينار الكويتي'  => ['kwd-usd','kwd-sar'],
        '💹 عالمي'            => ['usd-eur','usd-gbp','usd-cny','usd-jpy'],
        '🇹🇷 ليرة تركية'      => ['try-usd','try-sar'],
    ];

    foreach ($groups as $label => $slugs) {
        $lines[] = "\n<b>{$label}</b>";
        foreach ($slugs as $s) {
            if (!isset($pairs[$s])) continue;
            $pp = $pairs[$s];
            $r  = (float)($pp['rate_inv'] ?? 0);
            $rd = $r >= 1000 ? number_format($r,0,'.',',')
                : ($r >= 10 ? number_format($r,2) : number_format($r,4));
            $lines[] = "  {$pp['flag_from']}{$pp['flag_to']} {$pp['from']}/{$pp['to']}: {$rd}";
        }
    }

    $lines[] = "\n⚠️ أسعار مرجعية للاطلاع — ليست للتداول";
    $lines[] = "🌐 <a href=\"https://syp.yassota.com\">syp.yassota.com</a>";

    $msg = implode("\n", $lines);
    broadcast($BOT_TOKEN, $channels, $msg);
    $state['last_summary'] = $now;
    echo "Sent 3-hour comprehensive summary\n";
}

/* ── Save current rates as new baseline ────────────────────────── */
file_put_contents($cacheFile, json_encode($currentRates));
file_put_contents($stateFile, json_encode($state));

echo "[" . date('Y-m-d H:i:s') . "] Done.\n";
