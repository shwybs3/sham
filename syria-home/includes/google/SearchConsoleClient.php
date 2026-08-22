<?php
/** Search Console API v1 (https://developers.google.com/webmaster-tools/v1/searchanalytics/query) */
class SearchConsoleClient
{
    public static function listSites(): array {
        return GoogleOAuth::get('https://www.googleapis.com/webmasters/v3/sites');
    }

    /** Real keyword rank data — every query Search Console has actual
     *  impression data for, with its true average position. This is the
     *  only honest source of "keyword rank tracking": there is no free
     *  API that reports rank for keywords you don't already have search
     *  visibility on, and paid rank-tracking APIs aren't wired in here. */
    public static function searchAnalytics(string $siteUrl, int $days = 28, int $rowLimit = 250): array {
        $url = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteUrl) . '/searchAnalytics/query';
        return GoogleOAuth::postJson($url, [
            'startDate' => date('Y-m-d', strtotime("-{$days} days")),
            'endDate' => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['query'],
            'rowLimit' => min(1000, max(1, $rowLimit)),
        ]);
    }

    public static function totals(string $siteUrl, int $days = 28): array {
        $url = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteUrl) . '/searchAnalytics/query';
        return GoogleOAuth::postJson($url, [
            'startDate' => date('Y-m-d', strtotime("-{$days} days")),
            'endDate' => date('Y-m-d', strtotime('-1 day')),
        ]);
    }
}
