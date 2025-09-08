<?php
declare(strict_types=1);
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

allow_cors_options();

$kid_id = $_GET['kid_id'] ?? '';
if (!$kid_id) json_err('kid_id required', 400);

$pdo = db();
$ranges = jst_ranges();
$minStart = strtotime($ranges['thisMonth']['startUtc']);
$maxEnd   = time() + 60; // 未来少し猶予

// 今月に関係ありそうなセッション取得
$stmt = $pdo->prepare("
  SELECT label, started_at, ended_at
  FROM sessions
  WHERE kid_id = ?
    AND strftime('%s', started_at) < ?
    AND (
      (ended_at IS NOT NULL AND strftime('%s', ended_at) > ?) OR
      (strftime('%s', started_at) > ?)
    )
");
$stmt->execute([$kid_id, $maxEnd, $minStart, $minStart]);
$rows = $stmt->fetchAll();

$today=0; $yesterday=0; $month=0;
$now = time();

foreach ($rows as $r) {
  $s = strtotime($r['started_at']);
  $e = $r['ended_at'] ? strtotime($r['ended_at']) : $now;

  $today     += overlap_seconds($s, $e, strtotime($ranges['today']['startUtc']),     strtotime($ranges['today']['endUtc']));
  $yesterday += overlap_seconds($s, $e, strtotime($ranges['yesterday']['startUtc']), strtotime($ranges['yesterday']['endUtc']));
  $month     += overlap_seconds($s, $e, strtotime($ranges['thisMonth']['startUtc']), strtotime($ranges['thisMonth']['endUtc']));
}

$open = null;
foreach ($rows as $r) {
  if (!$r['ended_at']) { $open = ['label'=>$r['label'],'since'=>$r['started_at']]; break; }
}

json_ok([
  'ok'=>true,
  'kid_id'=>$kid_id,
  'now'=> $open ?? ['label'=>null,'since'=>null],
  'totals'=>[
    'today_sec'=>$today,
    'yesterday_sec'=>$yesterday,
    'this_month_sec'=>$month
  ],
]);