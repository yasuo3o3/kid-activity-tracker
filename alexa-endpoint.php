<?php
declare(strict_types=1);

// ====== 開発用ログ =======================================
const DEBUG_LOG = __DIR__ . '/alexa_debug.log';
function log_debug(string $m): void {
  file_put_contents(DEBUG_LOG, '['.date('c')."] $m\n", FILE_APPEND | LOCK_EX);
}

// ====== エラー応答 =====================================
function bad(int $code, string $msg): void {
  log_debug("BAD[$code] $msg");
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

// ====== Alexa署名検証 ===================================
function verify_alexa_signature(): array {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') bad(405, 'method not allowed');
  if (!str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) bad(400, 'invalid content type');
  
  $raw = file_get_contents('php://input') ?: '';
  if ($raw === '') bad(400, 'empty body');
  
  $hdr = array_change_key_case(getallheaders(), CASE_LOWER);
  $certUrl = $hdr['signaturecertchainurl'] ?? '';
  $signatureB64 = $hdr['signature'] ?? '';
  if (!$certUrl || !$signatureB64) bad(400, 'missing signature headers');
  
  // cert URL validation
  $u = parse_url($certUrl);
  if (!$u) bad(400, 'invalid cert url');
  if (($u['scheme'] ?? '') !== 'https') bad(400, 'cert url scheme');
  if (($u['host'] ?? '') !== 's3.amazonaws.com') bad(400, 'cert url host');
  if (($u['port'] ?? 443) != 443) bad(400, 'cert url port');
  if (!str_starts_with($u['path'] ?? '', '/echo.api/')) bad(400, 'cert url path');
  
  // fetch & verify cert
  $cacheFile = sys_get_temp_dir() . '/alexa_cert_' . md5($certUrl) . '.pem';
  $certPem = '';
  if (is_file($cacheFile) && filemtime($cacheFile) > time() - 3600) {
    $certPem = file_get_contents($cacheFile);
  } else {
    $ctx = stream_context_create([
      'http' => ['timeout' => 5],
      'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true]
    ]);
    $certPem = @file_get_contents($certUrl, false, $ctx);
    if (!$certPem) bad(400, 'fetch cert failed');
    file_put_contents($cacheFile, $certPem, LOCK_EX);
  }
  
  // parse & validate cert
  $cert = @openssl_x509_read($certPem);
  if (!$cert) bad(400, 'bad cert');
  $certInfo = openssl_x509_parse($cert);
  $validFrom = $certInfo['validFrom_time_t'] ?? 0;
  $validTo   = $certInfo['validTo_time_t'] ?? 0;
  $now = time();
  if (!($validFrom <= $now && $now <= $validTo)) bad(400, 'cert expired');
  
  $altNames = $certInfo['extensions']['subjectAltName'] ?? '';
  if (stripos($altNames, 'echo-api.amazon.com') === false) {
    bad(400, 'cert san mismatch');
  }
  
  // verify signature
  $pubKey = openssl_pkey_get_public($certPem);
  if (!$pubKey) bad(400, 'pubkey error');
  $sig = base64_decode($signatureB64, true);
  if ($sig === false) bad(400, 'b64 error');
  
  $ok = openssl_verify($raw, $sig, $pubKey, OPENSSL_ALGO_SHA1);
  if ($ok !== 1) bad(400, 'signature verify failed');
  
  // parse JSON
  $env = json_decode($raw, true);
  if (!is_array($env)) bad(400, 'json parse failed');
  
  // timestamp check (±150 sec)
  $tsStr = $env['request']['timestamp'] ?? null;
  if (!$tsStr) bad(400, 'no timestamp');
  $reqTs = strtotime($tsStr);
  if ($reqTs === false || abs(time() - $reqTs) > 150) bad(400, 'stale request');
  
  // application ID check (環境変数で設定)
  $expectedAppId = $_ENV['ALEXA_APPLICATION_ID'] ?? getenv('ALEXA_APPLICATION_ID');
  if ($expectedAppId) {
    $actualAppId = $env['context']['System']['application']['applicationId'] ?? '';
    if ($actualAppId !== $expectedAppId) bad(403, 'application id mismatch');
  }
  
  return $env;
}

// ====== スロット値抽出 ===================================
function extract_slot_value(?array $slot): ?string {
  if (!$slot) return null;
  // resolutions から canonical 値を優先
  $res = $slot['resolutions']['resolutionsPerAuthority'][0]['values'][0]['value']['name'] ?? null;
  if (is_string($res)) return $res;
  // なければ聞き取り文字列
  $val = $slot['value'] ?? null;
  return is_string($val) ? $val : null;
}

// ====== activity正規化 =================================
function normalize_activity(string $activity): ?string {
  $map = [
    '勉強' => 'study', 'べんきょう' => 'study', '公文' => 'study', 'くもん' => 'study', 'study' => 'study',
    '遊び' => 'play',  'あそび' => 'play',  'あそぶ' => 'play', 'ゲーム' => 'play', 'play' => 'play',
    '休憩' => 'break', 'きゅうけい' => 'break', '休む' => 'break', 'ブレイク' => 'break', '終了' => 'break', 'end' => 'break', 'break' => 'break',
  ];
  return $map[$activity] ?? null;
}

// ====== config.phpから kid_id逆引き ===================
function get_kid_id_by_name(string $name): ?string {
  $config_path = __DIR__ . '/config.php';
  if (!file_exists($config_path)) return null;
  
  $config = require $config_path;
  $kids = $config['kids'] ?? [];
  
  foreach ($kids as $kid_id => $kid_name) {
    if (strcasecmp($kid_name, $name) === 0) {
      return $kid_id;
    }
  }
  return null;
}

// ====== 内部API呼び出し ===============================
function call_switch_api(string $kid_name, string $activity): bool {
  $kid_id = get_kid_id_by_name($kid_name);
  if (!$kid_id) {
    log_debug("kid not found: $kid_name");
    return false;
  }
  
  $payload = json_encode([
    'kid_id' => $kid_id,
    'label' => $activity
  ]);
  
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => 'https://netservice.jp/kid-activity-tracker/api/switch.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_FOLLOWLOCATION => false,
  ]);
  
  $res = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);
  curl_close($ch);
  
  log_debug("switch_api kid_name=$kid_name kid_id=$kid_id activity=$activity http=$httpCode res=" . substr((string)$res,0,200));
  
  if ($res === false || $error) {
    log_debug("curl error: $error");
    return false;
  }
  
  if ($httpCode !== 200) return false;
  
  $json = json_decode($res, true);
  return is_array($json) && ($json['ok'] ?? false) === true;
}

// ====== Alexaレスポンス生成 ============================
function build_alexa_response(string $text, bool $endSession = true, ?string $repromptText = null): array {
  $response = [
    'version' => '1.0',
    'response' => [
      'shouldEndSession' => $endSession,
      'outputSpeech' => [
        'type' => 'PlainText',
        'text' => $text,
      ],
    ],
  ];
  
  if ($repromptText !== null) {
    $response['response']['reprompt'] = [
      'outputSpeech' => [
        'type' => 'PlainText',
        'text' => $repromptText
      ]
    ];
  }
  
  return $response;
}

// ====== メイン処理 ====================================
$env = verify_alexa_signature();
$type = $env['request']['type'] ?? '';

if ($type === 'LaunchRequest') {
  $response = build_alexa_response(
    'だれの、なにを開始しますか？',
    false,
    'お子さんの名前と、勉強、遊び、休憩のどれかを教えてください。'
  );
  echo json_encode($response, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($type === 'IntentRequest') {
  $intentName = $env['request']['intent']['name'] ?? '';
  $slots = $env['request']['intent']['slots'] ?? [];

  if ($intentName === 'StartActivityIntent') {
    $kid = extract_slot_value($slots['kid'] ?? null) ?? '';
    $activity_raw = extract_slot_value($slots['activity'] ?? null) ?? '';
    $activity = normalize_activity($activity_raw);

    if ($kid === '' || $activity === null) {
      $response = build_alexa_response(
        'すみません。だれの、なにを、もう一度お願いします。',
        false,
        'お子さんの名前と、勉強、遊び、休憩のどれかを教えてください。'
      );
      echo json_encode($response, JSON_UNESCAPED_UNICODE);
      exit;
    }

    $ok = call_switch_api($kid, $activity);
    if ($ok) {
      $ja = ($activity === 'study') ? '勉強' : (($activity === 'play') ? '遊び' : '休憩');
      $response = build_alexa_response("{$kid}の{$ja}を開始しました。");
      echo json_encode($response, JSON_UNESCAPED_UNICODE);
      exit;
    } else {
      $response = build_alexa_response('内部エラーが発生しました。しばらくしてからもう一度お試しください。');
      echo json_encode($response, JSON_UNESCAPED_UNICODE);
      exit;
    }
  }

  // 未対応Intent
  $response = build_alexa_response('すみません。その操作には対応していません。');
  echo json_encode($response, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($type === 'SessionEndedRequest') {
  $response = build_alexa_response('');
  echo json_encode($response, JSON_UNESCAPED_UNICODE);
  exit;
}

// その他
$response = build_alexa_response('不明なリクエストです。');
echo json_encode($response, JSON_UNESCAPED_UNICODE);