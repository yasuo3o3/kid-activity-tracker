<?php
declare(strict_types=1);

function json_ok(array $body, int $status=200): void {
  header("Content-Type: application/json");
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Headers: authorization, x-client-info, apikey, content-type");
  http_response_code($status);
  echo json_encode($body, JSON_UNESCAPED_UNICODE);
  exit;
}
function json_err(string $msg, int $status=400): void {
  json_ok(['ok'=>false,'error'=>$msg], $status);
}
function allow_cors_options(): void {
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: authorization, x-client-info, apikey, content-type");
    exit;
  }
}
// JSTの「今日/昨日/今月」範囲（UTC文字列で返却）
function jst_ranges(): array {
  $now = time();
  $jst = $now + 9*3600;
  $y = (int)gmdate('Y',$jst); $m=(int)gmdate('n',$jst); $d=(int)gmdate('j',$jst);
  $day0 = gmmktime(0,0,0,$m,$d,$y);
  $month0 = gmmktime(0,0,0,$m,1,$y);
  return [
    'nowUtc'    => gmdate('c', $now),
    'today'     => ['startUtc'=>gmdate('c',$day0-9*3600),    'endUtc'=>gmdate('c',$now)],
    'yesterday' => ['startUtc'=>gmdate('c',$day0-24*3600-9*3600), 'endUtc'=>gmdate('c',$day0-9*3600)],
    'thisMonth' => ['startUtc'=>gmdate('c',$month0-9*3600), 'endUtc'=>gmdate('c',$now)],
  ];
}
function overlap_seconds(int $aStart, int $aEnd, int $bStart, int $bEnd): int {
  $s = max($aStart, $bStart);
  $e = min($aEnd,   $bEnd);
  return max(0, (int)floor($e - $s));
}

function send_pushover_notification(string $message, string $title = 'Kid Activity'): bool {
  // config.phpから設定を読み込み
  $config_path = __DIR__ . '/../config.php';
  if (!file_exists($config_path)) {
    error_log('Pushover config not found');
    return false;
  }
  
  $config = require $config_path;
  $app_token = $config['pushover']['app_token'] ?? '';
  $user_key = $config['pushover']['user_key'] ?? '';
  
  if (!$app_token || !$user_key) {
    error_log('Pushover tokens not configured');
    return false;
  }

  $data = [
    'token' => $app_token,
    'user' => $user_key,
    'message' => $message,
    'title' => $title,
    'priority' => 0, // normal priority
  ];

  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.pushover.net/1/messages.json',
    CURLOPT_POSTFIELDS => http_build_query($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
  ]);
  
  $response = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  
  if ($http_code === 200 && $response !== false) {
    $result = json_decode($response, true);
    return isset($result['status']) && $result['status'] === 1;
  }
  
  error_log("Pushover failed: HTTP $http_code, Response: $response");
  return false;
}