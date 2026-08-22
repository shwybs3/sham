<?php
/**
 * cPanel UAPI client — real subdomain + MySQL provisioning.
 *
 * Needs an actual cPanel account: Settings > Subdomains > cPanel host,
 * username, and an API token (cPanel > Security > Manage API Tokens).
 * Every call here performs a REAL, live change on that hosting account —
 * there is no sandbox/dry-run mode on cPanel's side.
 *
 * UAPI conventions used here are standard across recent cPanel versions,
 * but some hosts customize theirs (VPS/reseller policies, disabled UAPI
 * token auth, etc.) — if a call fails, check the raw error message this
 * class surfaces before assuming the code is wrong.
 */
class CPanelClient
{
    public static function host(): string { return trim(setting('cpanel_host')); }
    public static function username(): string { return trim(setting('cpanel_username')); }
    public static function apiToken(): string { return trim(setting('cpanel_api_token')); }
    public static function rootDomain(): string { return trim(setting('cpanel_root_domain')); }

    public static function isConfigured(): bool {
        return self::host() !== '' && self::username() !== '' && self::apiToken() !== '';
    }

    private static function call(string $module, string $function, array $params = []): array {
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'cPanel is not configured. Add your host, username and API token in Settings > Subdomains.'];
        }
        $host = rtrim(self::host(), '/');
        if (!preg_match('~^https?://~', $host)) $host = 'https://' . $host;
        $url = $host . ':2083/execute/' . $module . '/' . $function . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: cpanel ' . self::username() . ':' . self::apiToken()],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($res === false) return ['ok' => false, 'error' => 'Could not reach cPanel: ' . $err];
        $data = json_decode($res, true);
        if (!is_array($data)) return ['ok' => false, 'error' => 'Unexpected response from cPanel.'];

        $status = $data['status'] ?? ($data['metadata']['result'] ?? null);
        if ($status === 1 || $status === true) {
            return ['ok' => true, 'data' => $data['data'] ?? $data['result']['data'] ?? null];
        }
        $errors = $data['errors'] ?? $data['metadata']['reason'] ?? null;
        $msg = is_array($errors) ? implode('; ', $errors) : ($errors ?: 'cPanel rejected the request.');
        return ['ok' => false, 'error' => $msg];
    }

    /** Creates a real subdomain. $dir is relative to the account's home (e.g. "public_html/blog"). */
    public static function createSubdomain(string $subLabel, string $dir): array {
        return self::call('SubDomain', 'addsubdomain', [
            'domain' => $subLabel,
            'rootdomain' => self::rootDomain(),
            'dir' => $dir,
        ]);
    }

    public static function createDatabase(string $dbName): array {
        return self::call('Mysql', 'create_database', ['name' => $dbName]);
    }

    public static function createDbUser(string $dbUser, string $password): array {
        return self::call('Mysql', 'create_user', ['name' => $dbUser, 'password' => $password]);
    }

    public static function grantAllPrivileges(string $dbUser, string $dbName): array {
        return self::call('Mysql', 'set_privileges_on_user', [
            'user' => $dbUser,
            'database' => $dbName,
            'privileges' => 'ALL PRIVILEGES',
        ]);
    }

    /** cPanel auto-prefixes DB/user names with "cpanelUser_" — build a short, safe base name. */
    public static function safeDbBaseName(string $label): string {
        $s = strtolower(preg_replace('~[^a-z0-9]+~i', '', $label));
        return substr($s !== '' ? $s : 'site' . time(), 0, 16);
    }
}
