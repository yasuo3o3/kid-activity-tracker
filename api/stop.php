<?php
declare(strict_types=1);
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

allow_cors_options();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$kid_id = $input['kid_id'] ?? '';
if (!$kid_id) {
  json_err('bad request', 400);
}

$pdo = db();
$now = gmdate('Y-m-d H:i:s');

// 直近の未終了セッション
$stmt = $pdo->prepare("SELECT id, label, started_at FROM sessions WHERE kid_id=? AND ended_at IS NULL ORDER BY started_at DESC LIMIT 1");
$stmt->execute([$kid_id]);
$open = $stmt->fetch();

if (!$open) {
  json_err('no active session', 400);
}

// セッションをクローズ
$pdo->prepare("UPDATE sessions SET ended_at=? WHERE id=?")->execute([$now, $open['id']]);

// 終了時の通知は送信しない（その日初回動作時のみ通知する方針）

json_ok(['ok'=>true, 'stopped'=>['label'=>$open['label'],'ended_at'=>$now]]);