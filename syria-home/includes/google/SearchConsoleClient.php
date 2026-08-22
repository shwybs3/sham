<?php
/** Search Console API v1 (https://developers.google.com/webmaster-tools/v1/searchanalytics/query) */
class SearchConsoleClient
{
    public static function listSites(): array {
        return GoogleOAuth::get('https://www.googleapis.com/webmasters/v3/sites');
    }

    public static function searchAnalytics(string $siteUrl, int $days = 28): array {
        $url = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteUrl) . '/searchAnalytics/query';
        return GoogleOAuth::postJson($url, [
            'startDate' => date('Y-m-d', strtotime("-{$days} days")),
            'endDate' => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['query'],
            'rowLimit' => 25,
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
