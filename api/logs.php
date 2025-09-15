<?php
declare(strict_types=1);
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

allow_cors_options();

$pdo = db();

// パラメータ取得
$kid_id = $_GET['kid_id'] ?? '';
$today_only = ($_GET['today_only'] ?? '') === 'true';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

if (empty($kid_id)) {
  json_error('kid_id is required');
}

// 子供の存在確認
$kid_stmt = $pdo->prepare("SELECT display_name FROM kids WHERE id = ? AND archived = 0");
$kid_stmt->execute([$kid_id]);
$kid = $kid_stmt->fetch();

if (!$kid) {
  json_error('Kid not found');
}

$kid_name = $kid['display_name'];

// ログクエリを構築
if ($today_only) {
  // 本日のログ全件（JSTの今日00:00:00以降）
  $ranges = jst_ranges();
  $today_start_utc = $ranges['today']['startUtc'];
  
  $stmt = $pdo->prepare("
    SELECT label, started_at, ended_at, created_at
    FROM sessions
    WHERE kid_id = ?
      AND started_at >= ?
    ORDER BY started_at DESC
  ");
  $stmt->execute([$kid_id, $today_start_utc]);
} else {
  // 最新N件のログ
  $stmt = $pdo->prepare("
    SELECT label, started_at, ended_at, created_at
    FROM sessions
    WHERE kid_id = ?
    ORDER BY started_at DESC
    LIMIT ?
  ");
  $stmt->execute([$kid_id, $limit]);
}

$sessions = $stmt->fetchAll();

// ログエントリを生成（開始・終了イベントを時系列で並べる）
$events = [];

foreach ($sessions as $session) {
  // 開始イベント
  $events[] = [
    'type' => 'start',
    'label' => $session['label'],
    'timestamp' => $session['started_at'],
    'display_time' => format_jst_datetime($session['started_at'])
  ];
  
  // 終了イベント（終了時刻がある場合のみ）
  if ($session['ended_at']) {
    $events[] = [
      'type' => 'end',
      'label' => $session['label'],
      'timestamp' => $session['ended_at'],
      'display_time' => format_jst_datetime($session['ended_at'])
    ];
  }
}

// 時系列で逆順ソート（最新順）
usort($events, function($a, $b) {
  return strcmp($b['timestamp'], $a['timestamp']);
});

// 本日のログ件数を取得（表示制御用）
$ranges = jst_ranges();
$today_start_utc = $ranges['today']['startUtc'];
$today_count_stmt = $pdo->prepare("
  SELECT COUNT(*) * 2 as event_count
  FROM sessions
  WHERE kid_id = ?
    AND started_at >= ?
");
$today_count_stmt->execute([$kid_id, $today_start_utc]);
$today_event_count = (int)$today_count_stmt->fetchColumn();

json_ok([
  'ok' => true,
  'kid_id' => $kid_id,
  'kid_name' => $kid_name,
  'events' => $events,
  'today_event_count' => $today_event_count,
  'showing_today_only' => $today_only,
  'limit_applied' => !$today_only ? $limit : null
]);

function format_jst_datetime($utc_timestamp) {
  // UTC "YYYY-MM-DD HH:MM:SS" 形式と ISO 形式の両方に対応
  if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $utc_timestamp)) {
    // 新形式: "YYYY-MM-DD HH:MM:SS" を UTC として解釈
    $dt = new DateTime($utc_timestamp . ' UTC');
  } else {
    // 既存形式: ISO 8601
    $dt = new DateTime($utc_timestamp, new DateTimeZone('UTC'));
  }
  $dt->setTimezone(new DateTimeZone('Asia/Tokyo'));
  return $dt->format('m/d H:i');
}