<?php
/**
 * NOWPayments crypto payment gateway client.
 * Mirrors the security pattern already used for the sibling yassota
 * site's gidx-payment-webhook.php: HMAC-SHA512 over the sorted-keys
 * JSON body, verified with a secret only you and NOWPayments know.
 *
 * Needs a real NOWPayments account: https://nowpayments.io — the API
 * key and IPN secret are entered in Settings > Payments, never here.
 */
class NOWPayments
{
    /** A safe, common subset of currencies NOWPayments supports. */
    const CURRENCIES = [
        'btc' => 'Bitcoin (BTC)',
        'eth' => 'Ethereum (ETH)',
        'usdttrc20' => 'Tether · TRC20 (USDT)',
        'usdterc20' => 'Tether · ERC20 (USDT)',
        'ltc' => 'Litecoin (LTC)',
        'doge' => 'Dogecoin (DOGE)',
        'trx' => 'Tron (TRX)',
        'bnbbsc' => 'BNB Smart Chain (BNB)',
    ];

    public static function apiKey(): string { return trim(setting('nowpayments_api_key')); }
    public static function ipnSecret(): string { return trim(setting('nowpayments_ipn_secret')); }
    public static function isConfigured(): bool { return self::apiKey() !== ''; }

    public static function webhookUrl(): string { return site_url('payment-webhook.php'); }

    /**
     * @return array{ok:bool, order_id?:string, pay_address?:string, pay_amount?:float, pay_currency?:string, error?:string}
     */
    public static function createPayment(float $priceUsd, string $payCurrency, string $orderId, string $description): array {
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'NOWPayments is not configured yet. Add an API key in Settings > Payments.'];
        }
        if (!array_key_exists($payCurrency, self::CURRENCIES)) {
            return ['ok' => false, 'error' => 'Unsupported currency.'];
        }

        $ch = curl_init('https://api.nowpayments.io/v1/payment');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'price_amount' => $priceUsd,
                'price_currency' => 'usd',
                'pay_currency' => $payCurrency,
                'order_id' => $orderId,
                'order_description' => $description,
                'ipn_callback_url' => self::webhookUrl(),
            ]),
            CURLOPT_HTTPHEADER => ['x-api-key: ' . self::apiKey(), 'Content-Type: application/json'],
            CURLOPT_TIMEOUT => 20,
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($res === false) return ['ok' => false, 'error' => 'Could not reach NOWPayments: ' . $err];
        $data = json_decode($res, true);
        if ($code < 200 || $code >= 300 || empty($data['pay_address'])) {
            return ['ok' => false, 'error' => 'Could not create invoice: ' . ($data['message'] ?? "HTTP $code")];
        }

        return [
            'ok' => true,
            'order_id' => $orderId,
            'pay_address' => $data['pay_address'],
            'pay_amount' => (float)($data['pay_amount'] ?? 0),
            'pay_currency' => $data['pay_currency'] ?? $payCurrency,
            'payment_id' => $data['payment_id'] ?? '',
        ];
    }

    private static function ipnSort(array $arr): array {
        ksort($arr);
        foreach ($arr as $k => $v) if (is_array($v)) $arr[$k] = self::ipnSort($v);
        return $arr;
    }

    public static function verifyIpnSignature(string $rawBody, string $receivedSignature): bool {
        $secret = self::ipnSecret();
        if ($secret === '' || $receivedSignature === '') return false;

        $data = json_decode($rawBody, true);
        if (!is_array($data)) return false;

        $canonical = json_encode(self::ipnSort($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expected = hash_hmac('sha512', $canonical, $secret);
        return hash_equals($expected, $receivedSignature);
    }
}
