<?php
/** Google Analytics Data API (GA4) v1beta (https://developers.google.com/analytics/devguide/reporting/data/v1) */
class AnalyticsDataClient
{
    /** $propertyId is the numeric GA4 property id, e.g. "123456789" (set in Settings). */
    public static function runReport(string $propertyId, int $days = 28): array {
        $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport";
        return GoogleOAuth::postJson($url, [
            'dateRanges' => [['startDate' => "{$days}daysAgo", 'endDate' => 'today']],
            'dimensions' => [['name' => 'pagePath']],
            'metrics' => [['name' => 'screenPageViews'], ['name' => 'activeUsers'], ['name' => 'averageSessionDuration']],
            'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
            'limit' => 10,
        ]);
    }

    public static function realtimeUsers(string $propertyId): array {
        $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runRealtimeReport";
        return GoogleOAuth::postJson($url, [
            'metrics' => [['name' => 'activeUsers']],
        ]);
    }
}
