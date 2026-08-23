<?php
/**
 * Promo code engine for the digital store.
 * A coupon either applies to one product (product_id set) or every
 * product (product_id NULL). max_uses NULL means unlimited.
 */

/** Generates a short, unambiguous random code (no 0/O/1/I). */
function coupon_random_code(int $length = 8): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $code;
}

/**
 * Validates a code against a specific product and current price.
 * @return array{ok:bool, error?:string, coupon?:array, discount_usd?:float, final_usd?:float}
 */
function coupon_validate(string $code, ?int $productId, float $priceUsd): array {
    global $pdo;
    $code = strtoupper(trim($code));
    if ($code === '') return ['ok' => false, 'error' => 'Enter a promo code.'];

    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $c = $stmt->fetch();
    if (!$c) return ['ok' => false, 'error' => 'That promo code was not found.'];
    if ($c['status'] !== 'active') return ['ok' => false, 'error' => 'That promo code is no longer active.'];
    if ($c['expires_at'] && strtotime($c['expires_at']) < time()) return ['ok' => false, 'error' => 'That promo code has expired.'];
    if ($c['max_uses'] !== null && (int)$c['used_count'] >= (int)$c['max_uses']) return ['ok' => false, 'error' => 'That promo code has reached its use limit.'];
    if ($c['product_id'] !== null && (int)$c['product_id'] !== (int)$productId) return ['ok' => false, 'error' => 'That promo code does not apply to this item.'];

    $discount = $c['discount_type'] === 'percent'
        ? round($priceUsd * (float)$c['discount_value'] / 100, 2)
        : (float)$c['discount_value'];
    $discount = min($discount, $priceUsd - 0.01); // never let a coupon zero out or invert the price
    $discount = max(0, $discount);

    return [
        'ok' => true,
        'coupon' => $c,
        'discount_usd' => $discount,
        'final_usd' => round($priceUsd - $discount, 2),
    ];
}

/** Call once a payment using this coupon actually confirms. */
function coupon_record_use(string $code): void {
    global $pdo;
    $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?")->execute([strtoupper(trim($code))]);
}
