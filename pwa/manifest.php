<?php
header('Content-Type: application/json');

$childId   = $_GET['child'] ?? '';
$childName = $_GET['name']  ?? 'Kid';

// 環境非依存の絶対パス生成
$appBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'); // pwa/の1つ上
if ($appBase === '') $appBase = '/';
$startUrl = $appBase . '/?child=' . rawurlencode($childId) . '&source=pwa';
$scope    = $appBase . '/';

$manifest = [
    "name" => "Kid Activity Tracker - " . $childName,
    "short_name" => $childName . " Tracker",
    "start_url" => $startUrl,
    "scope" => $scope,
    "display" => "standalone",
    "theme_color" => "#0ea5e9",
    "background_color" => "#ffffff",
    "icons" => [] // 既存があればそれを出力
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
?>