<?php
// 短縮URL→本来のPWAへリダイレクト
// GET パラメータ k を kid_id として本来のURLにリダイレクト

header('Content-Type: text/html; charset=UTF-8');

// k パラメータを取得
$k = $_GET['k'] ?? '';

if (empty($k)) {
    // k パラメータがない場合は親画面にリダイレクト
    header('Location: /kid-activity-tracker/', true, 302);
    exit;
}

// UUID 形式の簡易チェック（オプション）
// if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $k)) {
//     header('Location: /kid-activity-tracker/', true, 302);
//     exit;
// }

// URL エンコードして本来のPWAにリダイレクト
$redirectUrl = '/kid-activity-tracker/?kid_id=' . rawurlencode($k);
header('Location: ' . $redirectUrl, true, 302);
exit;
?>