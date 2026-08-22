<?php
/** AdSense Management API v2 (https://developers.google.com/adsense/management/reference/rest) */
class AdSenseClient
{
    public static function listAccounts(): array {
        return GoogleOAuth::get('https://adsense.googleapis.com/v2/accounts');
    }

    public static function earningsToday(string $accountName): array {
        $today = date('Y-m-d');
        $url = "https://adsense.googleapis.com/v2/{$accountName}/reports:generate"
            . '?dateRange=CUSTOM'
            . '&startDate.year=' . date('Y', strtotime($today)) . '&startDate.month=' . date('n', strtotime($today)) . '&startDate.day=' . date('j', strtotime($today))
            . '&endDate.year=' . date('Y', strtotime($today)) . '&endDate.month=' . date('n', strtotime($today)) . '&endDate.day=' . date('j', strtotime($today))
            . '&metrics=ESTIMATED_EARNINGS&metrics=CLICKS&metrics=IMPRESSIONS&metrics=PAGE_VIEWS';
        return GoogleOAuth::get($url);
    }
}
