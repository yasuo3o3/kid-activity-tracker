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