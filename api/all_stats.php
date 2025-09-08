<?php
declare(strict_types=1);
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';

allow_cors_options();

$pdo = db();

// 全ての子供を取得
$kids_stmt = $pdo->query("SELECT id, display_name FROM kids WHERE archived = 0 ORDER BY created_at");
$kids = $kids_stmt->fetchAll();

if (empty($kids)) {
  json_ok(['ok'=>true, 'kids'=>[]]);
}

$ranges = jst_ranges();
$minStart = strtotime($ranges['thisWeek']['startUtc']); // 今週から今月までカバー
$maxEnd   = time() + 60; // 未来少し猶予

$result_kids = [];

foreach ($kids as $kid) {
  $kid_id = $kid['id'];
  $kid_name = $kid['display_name'];
  
  // その子供のセッション取得
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

  $today=0; $yesterday=0; $week=0; $month=0;
  $today_by_activity = ['study'=>0, 'play'=>0, 'break'=>0];
  $week_by_activity = ['study'=>0, 'play'=>0, 'break'=>0];
  $month_by_activity = ['study'=>0, 'play'=>0, 'break'=>0];
  $now = time();

  foreach ($rows as $r) {
    $s = strtotime($r['started_at']);
    $e = $r['ended_at'] ? strtotime($r['ended_at']) : $now;

    $today_sec = overlap_seconds($s, $e, strtotime($ranges['today']['startUtc']),     strtotime($ranges['today']['endUtc']));
    $yesterday_sec = overlap_seconds($s, $e, strtotime($ranges['yesterday']['startUtc']), strtotime($ranges['yesterday']['endUtc']));
    $week_sec = overlap_seconds($s, $e, strtotime($ranges['thisWeek']['startUtc']), strtotime($ranges['thisWeek']['endUtc']));
    $month_sec = overlap_seconds($s, $e, strtotime($ranges['thisMonth']['startUtc']), strtotime($ranges['thisMonth']['endUtc']));
    
    $today += $today_sec;
    $yesterday += $yesterday_sec;
    $week += $week_sec;
    $month += $month_sec;
    
    // 活動別累計を計算
    if (isset($today_by_activity[$r['label']])) {
      $today_by_activity[$r['label']] += $today_sec;
    }
    if (isset($week_by_activity[$r['label']])) {
      $week_by_activity[$r['label']] += $week_sec;
    }
    if (isset($month_by_activity[$r['label']])) {
      $month_by_activity[$r['label']] += $month_sec;
    }
  }

  $open = null;
  foreach ($rows as $r) {
    if (!$r['ended_at']) { $open = ['label'=>$r['label'],'since'=>$r['started_at']]; break; }
  }

  $result_kids[] = [
    'kid_id'=>$kid_id,
    'kid_name'=>$kid_name,
    'now'=> $open ?? ['label'=>null,'since'=>null],
    'totals'=>[
      'today_sec'=>$today,
      'yesterday_sec'=>$yesterday,
      'this_week_sec'=>$week,
      'this_month_sec'=>$month
    ],
    'today_by_activity'=>[
      'study_sec'=>$today_by_activity['study'],
      'play_sec'=>$today_by_activity['play'],
      'break_sec'=>$today_by_activity['break']
    ],
    'week_by_activity'=>[
      'study_sec'=>$week_by_activity['study'],
      'play_sec'=>$week_by_activity['play'],
      'break_sec'=>$week_by_activity['break']
    ],
    'month_by_activity'=>[
      'study_sec'=>$month_by_activity['study'],
      'play_sec'=>$month_by_activity['play'],
      'break_sec'=>$month_by_activity['break']
    ]
  ];
}

json_ok(['ok'=>true, 'kids'=>$result_kids]);