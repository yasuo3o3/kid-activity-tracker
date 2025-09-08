<?php
declare(strict_types=1);
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

allow_cors_options();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$kid_id = $input['kid_id'] ?? '';
$label  = $input['label']  ?? '';
if (!$kid_id || !in_array($label, ['study','play','break'], true)) {
  json_err('bad request', 400);
}

$pdo = db();
$nowIso = gmdate('c');

// 直近の未終了セッション
$stmt = $pdo->prepare("SELECT id, label, started_at FROM sessions WHERE kid_id=? AND ended_at IS NULL ORDER BY started_at DESC LIMIT 1");
$stmt->execute([$kid_id]);
$open = $stmt->fetch();

// 連打ガード（同一ラベル60秒以内は409）
if ($open) {
  $since = strtotime($open['started_at']);
  if ($open['label'] === $label && (time() - $since) < 60) {
    json_err('too soon', 409);
  }
  // 前セッションをクローズ
  $pdo->prepare("UPDATE sessions SET ended_at=? WHERE id=?")->execute([$nowIso, $open['id']]);
}

// 新規セッション挿入
$id = uuid();
$pdo->prepare("INSERT INTO sessions (id, kid_id, label, started_at) VALUES (?,?,?,?)")
    ->execute([$id, $kid_id, $label, $nowIso]);

// その日初回動作時のみPushover通知を送信
$config_path = __DIR__ . '/../config.php';
$kid_name = 'お子さん'; // デフォルト名
if (file_exists($config_path)) {
  $config = require $config_path;
  $kid_name = $config['kids'][$kid_id] ?? 'お子さん';
}

// 当日の最初のセッションかチェック（JST 00:00 = UTC 15:00）
$jst_now = time() + 9*3600;
$today_date = gmdate('Y-m-d', $jst_now);
$today_start_utc = gmdate('Y-m-d H:i:s', strtotime($today_date . ' 00:00:00') - 9*3600); // JST 00:00をUTCに変換
$existing_stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE kid_id=? AND started_at >= ?");
$existing_stmt->execute([$kid_id, $today_start_utc]);
$is_first_today = $existing_stmt->fetchColumn() == 1; // 今挿入した分が1つ目

if ($is_first_today) {
  $time_jst = gmdate('H:i', time() + 9*3600);
  $message = "{$kid_name}が今日初めて活動を開始しました（{$time_jst}）";
  // 通知送信（失敗してもAPIレスポンスには影響させない）
  send_pushover_notification($message);
}

json_ok(['ok'=>true, 'current'=>['label'=>$label,'started_at'=>$nowIso]]);