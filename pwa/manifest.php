<?php
header('Content-Type: application/json');

$childId = $_GET['child'] ?? '';
$childName = $_GET['name'] ?? 'Kid';

// 子ども名が未指定の場合は、APIから取得を試みる（簡単な実装）
if (!$childName || $childName === 'Kid') {
    $childName = $childId ? 'Kid' : 'Tracker';
}

$manifest = [
    "name" => "Kid Activity Tracker - " . htmlspecialchars($childName),
    "short_name" => htmlspecialchars($childName) . " Tracker",
    "start_url" => "../?child=" . urlencode($childId) . "&source=pwa",
    "scope" => "../",
    "display" => "standalone",
    "theme_color" => "#0ea5e9",
    "background_color" => "#ffffff",
    "icons" => []
];

echo json_encode($manifest, JSON_PRETTY_PRINT);
?>