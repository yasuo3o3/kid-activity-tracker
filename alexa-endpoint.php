<?php
// alexa-endpoint.php
// -----------------------------------------------------------
// Alexa 署名検証 + Intent 振り分け + 応答生成（PHP 8想定）
// AWS不要 / XserverのHTTPSで利用可
// -----------------------------------------------------------

declare(strict_types=1);

// ====== 開発用ログ（本番は無効で可）=========================
const DEBUG_LOG = __DIR__ . '/alexa_debug.log';
function log_debug(string $m): void {
  file_put_contents(DEBUG_LOG, '['.date('c')."] $m\n", FILE_APPEND);
}

// ====== 署名検証 仕様 =======================================
// https://developer.amazon.com/en-US/docs/alexa/alexa-skills-kit-sdk-for-nodejs/manage-certificates.html
// ヘッダ: SignatureCertChainUrl, Signature
// - https / s3.amazonaws.com / port 443 / path startswith /echo.api/
// - 証明書の SAN に echo-api.amazon.com
// - 署名は RSA-SHA1
// - リクエストtimestampは ±150秒以内

function bad(int $code, string $msg): void {
  log_debug("BAD[$code] $msg");
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$raw = file_get_contents('php://input') ?: '';
if ($raw === '') bad(400, 'empty body');

$hdr = array_change_key_case(getallheaders(), CASE_LOWER);
$certUrl = $hdr['signaturecertchainurl'] ?? '';
$signatureB64 = $hdr['signature'] ?? '';
if (!$certUrl || !$signatureB64) bad(400, 'missing signature headers');

// ---- cert URL validation
$u = parse_url($certUrl);
if (!$u) bad(400, 'invalid cert url');
if (($u['scheme'] ?? '') !== 'https') bad(400, 'cert url scheme');
if (($u['host'] ?? '') !== 's3.amazonaws.com') bad(400, 'cert url host');
if (($u['port'] ?? 443) != 443) bad(400, 'cert url port');
if (!str_starts_with($u['path'] ?? '', '/echo.api/')) bad(400, 'cert url path');

// ---- fetch & verify cert (シンプルキャッシュ)
$cacheFile = sys_get_temp_dir() . '/alexa_cert_' . md5($certUrl) . '.pem';
$certPem = '';
if (is_file($cacheFile) && filemtime($cacheFile) > time() - 3600) {
  $certPem = file_get_contents($cacheFile);
} else {
  $ctx = stream_context_create([
    'http' => ['timeout' => 5],
    'ssl'  => ['capture_peer_cert' => false]
  ]);
  $certPem = @file_get_contents($certUrl, false, $ctx);
  if (!$certPem) bad(400, 'fetch cert failed');
  file_put_contents($cacheFile, $certPem);
}

// ---- parse & validate cert
$cert = @openssl_x509_read($certPem);
if (!$cert) bad(400, 'bad cert');
$certInfo = openssl_x509_parse($cert);
$validFrom = $certInfo['validFrom_time_t'] ?? 0;
$validTo   = $certInfo['validTo_time_t'] ?? 0;
$now = time();
if (!($validFrom <= $now && $now <= $validTo)) bad(400, 'cert expired');

$altNames = '';
if (!empty($certInfo['extensions']['subjectAltName'])) {
  $altNames = $certInfo['extensions']['subjectAltName'];
}
if (stripos($altNames, 'echo-api.amazon.com') === false) {
  bad(400, 'cert san mismatch');
}

// ---- verify signature (RSA-SHA1)
$pubKey = openssl_pkey_get_public($certPem);
if (!$pubKey) bad(400, 'pubkey error');
$sig = base64_decode($signatureB64, true);
if ($sig === false) bad(400, 'b64 error');

$ok = openssl_verify($raw, $sig, $pubKey, OPENSSL_ALGO_SHA1);
if ($ok !== 1) bad(400, 'signature verify failed');

// ---- parse JSON
$env = json_decode($raw, true);
if (!is_array($env)) bad(400, 'json parse failed');

// ---- timestamp check (±150 sec)
$tsStr = $env['request']['timestamp'] ?? null;
if (!$tsStr) bad(400, 'no timestamp');
$reqTs = strtotime($tsStr);
if ($reqTs === false || abs(time() - $reqTs) > 150) bad(400, 'stale request');

// ====== ここからスキル本体ロジック ==========================

function buildSpeech(string $text, bool $endSession=true): array {
  return [
    'version' => '1.0',
    'response' => [
      'shouldEndSession' => $endSession,
      'outputSpeech' => [
        'type' => 'PlainText',
        'text' => $text,
      ],
    ],
  ];
}

function slotValue(?array $slot): ?string {
  if (!$slot) return null;
  // resolutions から canonical 値を優先
  $res = $slot['resolutions']['resolutionsPerAuthority'][0]['values'][0]['value']['name'] ?? null;
  if (is_string($res)) return $res;
  // なければ聞き取り文字列
  $val = $slot['value'] ?? null;
  return is_string($val) ? $val : null;
}

// ---- ここで既存のPHP APIにつなぐ設定（あなたの環境に合わせて編集）
const INTERNAL_SWITCH_URL = 'https://netservice.jp/kid-activity-tracker/api/switch.php';
// 例: POST { kid: 'こども名', activity: 'study|play|break' } を受ける想定
// 既に別のパス/パラメータならこの定数と送信部分を調整してください。

function call_switch_api(string $kid, string $activity): bool {
  $ctx = stream_context_create([
    'http' => [
      'method'  => 'POST',
      'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
      'content' => http_build_query(['kid' => $kid, 'activity' => $activity]),
      'timeout' => 5,
    ]
  ]);
  $res = @file_get_contents(INTERNAL_SWITCH_URL, false, $ctx);
  log_debug("switch_api kid=$kid activity=$activity res=" . substr((string)$res,0,200));
  // API側の返り値仕様に合わせて判定してください。ここでは200到達でOK扱い。
  return $res !== false;
}

// ---- リクエストの種類で分岐
$type = $env['request']['type'] ?? '';

if ($type === 'LaunchRequest') {
  echo json_encode(buildSpeech('だれの、なにを開始しますか？'), JSON_UNESCAPED_UNICODE);
  exit;
}

if ($type === 'IntentRequest') {
  $intentName = $env['request']['intent']['name'] ?? '';
  $slots = $env['request']['intent']['slots'] ?? [];

  if ($intentName === 'StartActivityIntent') {
    $kid = slotValue($slots['kid'] ?? null) ?? '';
    $activity = slotValue($slots['activity'] ?? null) ?? '';

    // activity 同義語の軽い正規化（必要に応じて調整）
    // ACTIVITY スロットの canonical 値名を study/play/break にしておくのが本筋
    $map = [
      '勉強' => 'study', 'べんきょう' => 'study', '公文' => 'study', 'くもん' => 'study', 'study' => 'study',
      '遊び' => 'play',  'あそび' => 'play',  'あそぶ' => 'play', 'ゲーム' => 'play', 'play' => 'play',
      '休憩' => 'break', 'きゅうけい' => 'break', '休む' => 'break', 'ブレイク' => 'break', '終了' => 'break', 'end' => 'break', 'break' => 'break',
    ];
    if (isset($map[$activity])) $activity = $map[$activity];

    if ($kid === '' || !in_array($activity, ['study','play','break'], true)) {
      echo json_encode(buildSpeech('すみません。だれの、なにを、もう一度お願いします。'), JSON_UNESCAPED_UNICODE);
      exit;
    }

    // ここで内部APIを叩く（失敗時はメッセージ出し分け）
    $ok = call_switch_api($kid, $activity);
    if ($ok) {
      $ja = ($activity === 'study') ? '勉強' : (($activity === 'play') ? '遊び' : '休憩');
      echo json_encode(buildSpeech("{$kid}の{$ja}を開始しました。"), JSON_UNESCAPED_UNICODE);
      exit;
    } else {
      echo json_encode(buildSpeech('内部エラーが発生しました。しばらくしてからもう一度お試しください。'), JSON_UNESCAPED_UNICODE);
      exit;
    }
  }

  // 未対応Intent
  echo json_encode(buildSpeech('すみません。その操作には対応していません。'), JSON_UNESCAPED_UNICODE);
  exit;
}

if ($type === 'SessionEndedRequest') {
  echo json_encode(buildSpeech(''), JSON_UNESCAPED_UNICODE);
  exit;
}

// その他
echo json_encode(buildSpeech('不明なリクエストです。'), JSON_UNESCAPED_UNICODE);
