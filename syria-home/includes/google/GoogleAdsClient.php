<?php
/**
 * Google Ads API v17 (https://developers.google.com/google-ads/api/docs/rest/overview)
 * NOTE: Unlike the other three APIs, Google Ads also requires a
 * "developer token" issued to your Google Ads Manager account (separate
 * from the OAuth Client ID/Secret) — see Settings -> API Keys. Without one,
 * every call below returns a clear "developer token missing" error instead
 * of a raw API failure.
 */
class GoogleAdsClient
{
    public static function developerToken(): string { return trim(setting('google_ads_developer_token')); }
    public static function customerId(): string { return trim(setting('google_ads_customer_id')); }

    public static function isConfigured(): bool {
        return self::developerToken() !== '' && self::customerId() !== '' && GoogleOAuth::isConnected();
    }

    public static function search(string $gaql): array {
        if (self::developerToken() === '') {
            return ['error' => 'developer_token_missing', 'message' => 'Add your Google Ads developer token in Settings > API Keys.'];
        }
        if (self::customerId() === '') {
            return ['error' => 'customer_id_missing', 'message' => 'Add your Google Ads Customer ID in Settings > API Keys.'];
        }
        $cid = preg_replace('~\D~', '', self::customerId());
        $url = "https://googleads.googleapis.com/v17/customers/{$cid}/googleAds:search";
        return GoogleOAuth::postJson($url, ['query' => $gaql], [
            'developer-token: ' . self::developerToken(),
        ]);
    }

    public static function campaignSummary(int $days = 30): array {
        $gaql = "SELECT campaign.name, metrics.impressions, metrics.clicks, metrics.cost_micros "
              . "FROM campaign WHERE segments.date DURING LAST_{$days}_DAYS";
        return self::search($gaql);
    }
}
