<?php
/**
 * ═══════════════════════════════════════════════
 * Aggregates AdSense + Search Console + Analytics into one payload for
 * the dashboard.
 *
 * Results are cached in the settings table, because the dashboard is the
 * first page loaded after every login and four uncached API round-trips
 * would add seconds to it and burn daily quota for no benefit. Each API
 * is fetched inside its own try/catch so one unavailable product never
 * blanks the whole panel.
 * ═══════════════════════════════════════════════
 */
class GoogleStats
{
    /** How long a cached snapshot stays fresh, in seconds. */
    const TTL = 900;

    public static function isAvailable(): bool {
        return GoogleOAuth::isConnected();
    }

    /**
     * @return array{fetched_at:int,stale:bool,adsense:?array,gsc:?array,ga4:?array,errors:array}
     */
    public static function summary(bool $force = false): array
    {
        if (!self::isAvailable()) {
            return ['fetched_at' => 0, 'stale' => true, 'adsense' => null, 'gsc' => null, 'ga4' => null,
                    'errors' => ['Google account not connected.']];
        }

        if (!$force) {
            $cached = json_decode((string)setting('gstats_cache', ''), true);
            if (is_array($cached) && (time() - (int)($cached['fetched_at'] ?? 0)) < self::TTL) {
                $cached['stale'] = false;
                return $cached;
            }
        }

        $out = ['fetched_at' => time(), 'stale' => false,
                'adsense' => null, 'gsc' => null, 'ga4' => null, 'errors' => []];

        try { $out['adsense'] = self::adsense(); }
        catch (Throwable $e) { $out['errors'][] = 'AdSense: ' . $e->getMessage(); }

        try { $out['gsc'] = self::searchConsole(); }
        catch (Throwable $e) { $out['errors'][] = 'Search Console: ' . $e->getMessage(); }

        try { $out['ga4'] = self::analytics(); }
        catch (Throwable $e) { $out['errors'][] = 'Analytics: ' . $e->getMessage(); }

        set_setting('gstats_cache', json_encode($out));
        return $out;
    }

    /** Today's estimated earnings, clicks, impressions and page views. */
    private static function adsense(): ?array
    {
        $accounts = AdSenseClient::listAccounts();
        if (!empty($accounts['error']) || empty($accounts['accounts'][0]['name'])) return null;

        $name = $accounts['accounts'][0]['name'];
        $rep  = AdSenseClient::earningsToday($name);
        if (!empty($rep['error'])) return null;

        /* The reports API returns headers + one totals row; map by header name
           rather than position, since metric order isn't contractual. */
        $headers = array_map(fn($h) => $h['name'] ?? '', $rep['headers'] ?? []);
        $cells   = array_map(fn($c) => $c['value'] ?? '0', $rep['totals']['cells'] ?? []);
        $byName  = [];
        foreach ($headers as $i => $h) { $byName[$h] = $cells[$i] ?? '0'; }

        return [
            'currency'    => $rep['headers'][0]['currencyCode'] ?? 'USD',
            'earnings'    => (float)($byName['ESTIMATED_EARNINGS'] ?? 0),
            'clicks'      => (int)($byName['CLICKS'] ?? 0),
            'impressions' => (int)($byName['IMPRESSIONS'] ?? 0),
            'page_views'  => (int)($byName['PAGE_VIEWS'] ?? 0),
        ];
    }

    /** 28-day clicks / impressions / CTR / average position, plus top queries. */
    private static function searchConsole(): ?array
    {
        $site = trim(setting('gsc_site_url', ''));
        if ($site === '') return null;

        $totals = SearchConsoleClient::totals($site, 28);
        if (!empty($totals['error'])) return null;

        $row = $totals['rows'][0] ?? null;
        $out = [
            'clicks'      => (int)($row['clicks'] ?? 0),
            'impressions' => (int)($row['impressions'] ?? 0),
            'ctr'         => (float)($row['ctr'] ?? 0),
            'position'    => (float)($row['position'] ?? 0),
            'queries'     => [],
        ];

        $q = SearchConsoleClient::searchAnalytics($site, 28, 8);
        foreach ($q['rows'] ?? [] as $r) {
            $out['queries'][] = [
                'query'       => $r['keys'][0] ?? '',
                'clicks'      => (int)($r['clicks'] ?? 0),
                'impressions' => (int)($r['impressions'] ?? 0),
                'position'    => round((float)($r['position'] ?? 0), 1),
            ];
        }
        return $out;
    }

    /** Live users now, plus 28-day page views / users and the top pages. */
    private static function analytics(): ?array
    {
        $prop = trim(setting('ga4_property_id', ''));
        if ($prop === '') return null;

        $out = ['live' => 0, 'page_views' => 0, 'users' => 0, 'pages' => []];

        $rt = AnalyticsDataClient::realtimeUsers($prop);
        if (empty($rt['error'])) {
            $out['live'] = (int)($rt['rows'][0]['metricValues'][0]['value'] ?? 0);
        }

        $rep = AnalyticsDataClient::runReport($prop, 28);
        if (!empty($rep['error'])) return $out;

        foreach ($rep['rows'] ?? [] as $r) {
            $views = (int)($r['metricValues'][0]['value'] ?? 0);
            $users = (int)($r['metricValues'][1]['value'] ?? 0);
            $out['page_views'] += $views;
            $out['users']      += $users;
            if (count($out['pages']) < 8) {
                $out['pages'][] = ['path' => $r['dimensionValues'][0]['value'] ?? '/', 'views' => $views];
            }
        }
        return $out;
    }
}
