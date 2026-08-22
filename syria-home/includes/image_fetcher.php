<?php
/**
 * Fetches an image from an admin-supplied URL, verifies it's actually an
 * image, resizes/compresses it with GD, and saves the local copy under
 * uploads/images/ — so the site never hotlinks external images at render
 * time, and every stored image is a controlled, compressed size.
 */
function sh_fetch_and_store_image(string $url, string $subdir = 'articles', int $maxWidth = 1600, int $quality = 82): array {
    if (!preg_match('~^https?://~i', $url)) {
        return ['ok' => false, 'error' => 'Only http(s) image URLs are supported.'];
    }

    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return ['ok' => false, 'error' => 'Invalid URL.'];
    $ip = gethostbyname($host);
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return ['ok' => false, 'error' => 'That host cannot be reached (private/internal address).'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SyriaHomeImageFetcher/1.0)',
        CURLOPT_RANGE => '0-10485760', // best-effort cap ~10MB; some servers ignore Range and send full body anyway
    ]);
    $data = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($data === false) return ['ok' => false, 'error' => 'Could not download: ' . $err];
    if ($code >= 400) return ['ok' => false, 'error' => "Server returned HTTP $code."];
    if (strlen($data) > 10485760) return ['ok' => false, 'error' => 'Image is larger than the 10MB limit.'];

    $info = @getimagesizefromstring($data);
    if (!$info) return ['ok' => false, 'error' => 'That URL did not return a valid image.'];

    $img = @imagecreatefromstring($data);
    if (!$img) return ['ok' => false, 'error' => 'Could not process that image format.'];

    $w = imagesx($img);
    $h = imagesy($img);
    if ($w > $maxWidth) {
        $newH = max(1, (int)round($h * ($maxWidth / $w)));
        $resized = imagecreatetruecolor($maxWidth, $newH);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxWidth, $newH, $w, $h);
        imagedestroy($img);
        $img = $resized;
    }

    $dir = UPLOAD_PATH . '/images/' . preg_replace('~[^a-z0-9_-]~i', '', $subdir);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return ['ok' => false, 'error' => 'Could not create the uploads directory (check permissions).'];
    }

    $filename = bin2hex(random_bytes(10)) . '.jpg';
    $fullPath = $dir . '/' . $filename;
    $saved = imagejpeg($img, $fullPath, $quality);
    imagedestroy($img);

    if (!$saved) return ['ok' => false, 'error' => 'Could not save the compressed image to disk.'];

    $relPath = 'uploads/images/' . preg_replace('~[^a-z0-9_-]~i', '', $subdir) . '/' . $filename;
    return ['ok' => true, 'path' => $relPath, 'url' => site_url($relPath), 'bytes' => filesize($fullPath)];
}

/**
 * Rewrites every <img src="http(s)://..."> in $html to point at a
 * locally stored, compressed copy — leaves already-local/relative src
 * values untouched, and silently leaves any image that fails to fetch
 * pointing at its original URL rather than breaking the article.
 */
function sh_localize_body_images(string $html, string $subdir = 'articles'): string {
    return preg_replace_callback('~<img\b([^>]*?)\bsrc=(["\'])(.*?)\2([^>]*)>~i', function ($m) use ($subdir) {
        $before = $m[1];
        $src = $m[3];
        $after = $m[4];
        if (!preg_match('~^https?://~i', $src)) return $m[0];

        $result = sh_fetch_and_store_image($src, $subdir);
        if (!$result['ok']) return $m[0];

        $rebuilt = '<img' . $before . ' src="' . e($result['url']) . '"' . $after;
        if (stripos($rebuilt, 'loading=') === false) $rebuilt .= ' loading="lazy"';
        return $rebuilt . '>';
    }, $html);
}
