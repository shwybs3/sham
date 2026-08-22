<?php
/**
 * Minimal OAuth2 (authorization-code) client for Google APIs.
 * One connection/grant is used for all four Google products (AdSense,
 * Search Console, Analytics Data, Ads) since Google issues a single
 * access token covering every scope you request in one consent screen.
 *
 * Requires a real OAuth Client ID/Secret from Google Cloud Console
 * (APIs & Services -> Credentials -> OAuth client ID -> Web application),
 * with this file's redirect URL added to "Authorized redirect URIs":
 *   https://<your-domain>/admin/google-callback.php
 */
class GoogleOAuth
{
    const SCOPES = [
        'https://www.googleapis.com/auth/adsense.readonly',
        'https://www.googleapis.com/auth/webmasters.readonly',
        'https://www.googleapis.com/auth/analytics.readonly',
        'https://www.googleapis.com/auth/adwords',
        'openid',
        'email',
    ];

    public static function clientId(): string { return trim(setting('google_client_id')); }
    public static function clientSecret(): string { return trim(setting('google_client_secret')); }
    public static function redirectUri(): string { return site_url('admin/google-callback.php'); }

    public static function isConfigured(): bool {
        return self::clientId() !== '' && self::clientSecret() !== '';
    }

    public static function authorizeUrl(): string {
        $params = [
            'client_id' => self::clientId(),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'scope' => implode(' ', self::SCOPES),
            'state' => csrf_token(),
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public static function exchangeCode(string $code): array {
        $resp = self::post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        if (!empty($resp['access_token'])) self::storeTokens($resp);
        return $resp;
    }

    public static function storeTokens(array $resp): void {
        global $pdo;
        $expiresAt = date('Y-m-d H:i:s', time() + (int)($resp['expires_in'] ?? 3600));
        $stmt = $pdo->prepare("INSERT INTO google_tokens (service, access_token, refresh_token, expires_at, scope)
            VALUES ('google', ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE access_token = VALUES(access_token),
                refresh_token = COALESCE(NULLIF(VALUES(refresh_token), ''), refresh_token),
                expires_at = VALUES(expires_at), scope = VALUES(scope)");
        $stmt->execute([$resp['access_token'], $resp['refresh_token'] ?? '', $expiresAt, $resp['scope'] ?? implode(' ', self::SCOPES)]);
    }

    public static function isConnected(): bool {
        global $pdo;
        $row = $pdo->query("SELECT * FROM google_tokens WHERE service='google'")->fetch();
        return $row && !empty($row['refresh_token']);
    }

    public static function disconnect(): void {
        global $pdo;
        $pdo->exec("DELETE FROM google_tokens WHERE service='google'");
    }

    /** Returns a valid access token, refreshing it first if it's expired. */
    public static function accessToken(): ?string {
        global $pdo;
        $row = $pdo->query("SELECT * FROM google_tokens WHERE service='google'")->fetch();
        if (!$row) return null;
        if (strtotime($row['expires_at']) - time() > 60) return $row['access_token'];
        if (empty($row['refresh_token'])) return null;

        $resp = self::post('https://oauth2.googleapis.com/token', [
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'refresh_token' => $row['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);
        if (empty($resp['access_token'])) return null;
        $resp['refresh_token'] = $row['refresh_token'];
        self::storeTokens($resp);
        return $resp['access_token'];
    }

    private static function post(string $url, array $fields): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $json = json_decode((string)$body, true);
        return is_array($json) ? $json : ['error' => 'invalid_response', 'raw' => $body];
    }

    public static function get(string $url): array {
        $token = self::accessToken();
        if (!$token) return ['error' => 'not_connected'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $json = json_decode((string)$body, true);
        return is_array($json) ? $json : ['error' => 'invalid_response', 'raw' => $body];
    }

    public static function postJson(string $url, array $payload, array $extraHeaders = []): array {
        $token = self::accessToken();
        if (!$token) return ['error' => 'not_connected'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array_merge([
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ], $extraHeaders),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $json = json_decode((string)$body, true);
        return is_array($json) ? $json : ['error' => 'invalid_response', 'raw' => $body];
    }
}
