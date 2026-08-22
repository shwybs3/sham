<?php
/**
 * functions.php — Google Indexing API + NOWPayments helpers
 */

// ── Google Service Account JWT ────────────────────────────────────────────────
function validate_sa_key(string $raw): array {
    $d = json_decode($raw, true);
    if (!is_array($d)) return ['ok'=>false,'error'=>'File is not valid JSON'];
    if (($d['type']??'') !== 'service_account') return ['ok'=>false,'error'=>'type must be service_account'];
    if (empty($d['private_key'])||empty($d['client_email'])) return ['ok'=>false,'error'=>'Missing private_key or client_email'];
    return ['ok'=>true,'client_email'=>$d['client_email']];
}

function get_access_token(string $saJson): array {
    $sa = json_decode($saJson, true);
    if (!$sa||empty($sa['private_key'])||empty($sa['client_email'])) return ['ok'=>false,'error'=>'Invalid key'];
    $now = time();
    $b64 = fn($d)=>rtrim(strtr(base64_encode(json_encode($d)),'+/','-_'),'=');
    $hdr = $b64(['alg'=>'RS256','typ'=>'JWT']);
    $pay = $b64(['iss'=>$sa['client_email'],'scope'=>'https://www.googleapis.com/auth/indexing',
        'aud'=>'https://oauth2.googleapis.com/token','iat'=>$now,'exp'=>$now+3600]);
    $sig=''; if (!openssl_sign("$hdr.$pay",$sig,$sa['private_key'],'SHA256')) return ['ok'=>false,'error'=>'JWT signing failed'];
    $jwt = "$hdr.$pay.".rtrim(strtr(base64_encode($sig),'+/','-_'),'=');
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>20,
        CURLOPT_POSTFIELDS=>http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt])]);
    $res=curl_exec($ch); $err=curl_error($ch); curl_close($ch);
    if ($res===false) return ['ok'=>false,'error'=>'Cannot connect to Google: '.$err];
    $data=json_decode($res,true);
    if (empty($data['access_token'])) return ['ok'=>false,'error'=>'Google rejected: '.($data['error_description']??$data['error']??'unknown')];
    return ['ok'=>true,'token'=>$data['access_token']];
}

function submit_url(string $token, string $url): array {
    $ch=curl_init('https://indexing.googleapis.com/v3/urlNotifications:publish');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>15,
        CURLOPT_POSTFIELDS=>json_encode(['url'=>$url,'type'=>'URL_UPDATED']),
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json']]);
    $res=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if ($res===false) return ['ok'=>false,'message'=>'Connection error'];
    $data=json_decode($res,true);
    if ($code>=200&&$code<300) return ['ok'=>true,'message'=>'Submitted successfully'];
    $msg=$data['error']['message']??"HTTP $code";
    if ($code===403) $msg='Account is not a verified owner in Search Console or Indexing API not enabled';
    if ($code===429) $msg='Daily Google quota exceeded';
    return ['ok'=>false,'message'=>$msg];
}

function fetch_sitemap(string $sitemapUrl): array {
    $ch=curl_init($sitemapUrl);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; IndexPro/1.0)']);
    $res=curl_exec($ch); $err=curl_error($ch); curl_close($ch);
    if ($res===false) return ['ok'=>false,'error'=>'Sitemap fetch failed: '.$err,'urls'=>[]];
    $urls=[];
    // Handle sitemap index
    if (preg_match_all('/<sitemap>.*?<loc>(.*?)<\/loc>.*?<\/sitemap>/si',$res,$m)) {
        foreach ($m[1] as $sub) {
            $sub=trim(html_entity_decode($sub,ENT_QUOTES,'UTF-8'));
            if (filter_var($sub,FILTER_VALIDATE_URL)) {
                $sub_result=fetch_sitemap($sub);
                if ($sub_result['ok']) $urls=array_merge($urls,$sub_result['urls']);
            }
        }
    }
    if (preg_match_all('/<url>.*?<loc>(.*?)<\/loc>.*?<\/url>/si',$res,$m)) {
        foreach ($m[1] as $u) { $u=trim(html_entity_decode($u,ENT_QUOTES,'UTF-8')); if(filter_var($u,FILTER_VALIDATE_URL)) $urls[]=$u; }
    } elseif (preg_match_all('/<loc>(.*?)<\/loc>/si',$res,$m)) {
        foreach ($m[1] as $u) { $u=trim(html_entity_decode($u,ENT_QUOTES,'UTF-8')); if(filter_var($u,FILTER_VALIDATE_URL)) $urls[]=$u; }
    }
    $urls=array_values(array_unique($urls));
    return ['ok'=>true,'urls'=>$urls,'count'=>count($urls)];
}

// ── NOWPayments ───────────────────────────────────────────────────────────────
function nowpay_create(string $uid, string $planKey, string $currency): array {
    $plans=PLANS; $plan=$plans[$planKey]??null;
    if (!$plan||$plan['price']<=0) return ['ok'=>false,'error'=>'Invalid plan'];
    $apiKey=cfg('np_api_key',NP_API_KEY);
    if (!$apiKey) return ['ok'=>false,'error'=>'NOWPayments API key not configured'];
    $orderId=$uid.'_'.$planKey.'_'.bin2hex(random_bytes(4));
    $ch=curl_init('https://api.nowpayments.io/v1/payment');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>20,
        CURLOPT_POSTFIELDS=>json_encode(['price_amount'=>$plan['price'],'price_currency'=>'usd',
            'pay_currency'=>$currency,'order_id'=>$orderId,
            'order_description'=>SITE_NAME.' '.$plan['name'].' Plan — '.$plan['days'].' days',
            'ipn_callback_url'=>site_url('webhook.php')]),
        CURLOPT_HTTPHEADER=>['x-api-key: '.$apiKey,'Content-Type: application/json']]);
    $res=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if ($res===false) return ['ok'=>false,'error'=>'Cannot connect to NOWPayments'];
    $data=json_decode($res,true);
    if ($code<200||$code>=300||empty($data['pay_address'])) return ['ok'=>false,'error'=>'Invoice creation failed: '.($data['message']??"HTTP $code")];
    $pay=['order_id'=>$orderId,'uid'=>$uid,'plan'=>$planKey,'price_usd'=>$plan['price'],
        'days'=>$plan['days'],'pay_currency'=>$data['pay_currency']??$currency,
        'pay_address'=>$data['pay_address'],'pay_amount'=>$data['pay_amount']??0,
        'actually_paid'=>null,'status'=>'pending','created_at'=>now()];
    pay_save($pay);
    return ['ok'=>true,'order_id'=>$orderId,'data'=>$data];
}

// ── Google OAuth ──────────────────────────────────────────────────────────────
function google_oauth_url(): string {
    $cid=cfg('google_client_id',GOOGLE_CLIENT_ID); if(!$cid) return '';
    $state=bin2hex(random_bytes(8)); $_SESSION['oauth_state']=$state;
    return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
        'client_id'=>$cid,'redirect_uri'=>site_url('index.php?action=oauth_callback'),
        'response_type'=>'code','scope'=>'openid email profile','state'=>$state,'access_type'=>'online']);
}
function google_oauth_exchange(string $code): array {
    $cid=cfg('google_client_id',GOOGLE_CLIENT_ID); $csec=cfg('google_client_secret',GOOGLE_CLIENT_SECRET);
    if (!$cid||!$csec) return ['ok'=>false,'error'=>'OAuth not configured'];
    $ch=curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>15,
        CURLOPT_POSTFIELDS=>http_build_query(['code'=>$code,'client_id'=>$cid,'client_secret'=>$csec,
            'redirect_uri'=>site_url('index.php?action=oauth_callback'),'grant_type'=>'authorization_code'])]);
    $res=curl_exec($ch); curl_close($ch);
    $tok=json_decode($res,true); if(empty($tok['id_token'])) return ['ok'=>false,'error'=>'Token exchange failed'];
    // Decode JWT payload (no verification needed for our use)
    $parts=explode('.',$tok['id_token']);
    $payload=json_decode(base64_decode(str_pad(strtr($parts[1]??'','-_','+/'),strlen($parts[1]??'')%4,'=',STR_PAD_RIGHT)),true);
    if(empty($payload['sub'])) return ['ok'=>false,'error'=>'Invalid ID token'];
    return ['ok'=>true,'sub'=>$payload['sub'],'email'=>$payload['email']??'','name'=>$payload['name']??'','picture'=>$payload['picture']??''];
}
