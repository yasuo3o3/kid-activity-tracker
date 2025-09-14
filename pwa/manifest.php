<?php
header('Content-Type: application/json');

$childId = $_GET['child'] ?? '';
$childName = $_GET['name'] ?? 'Kid';

$manifest = [
    "name" => "Kid Activity Tracker - " . htmlspecialchars($childName),
    "short_name" => htmlspecialchars($childName) . " Tracker",
    "start_url" => "/?child=" . urlencode($childId) . "&source=pwa",
    "scope" => "/",
    "display" => "standalone",
    "theme_color" => "#0ea5e9",
    "background_color" => "#ffffff",
    "icons" => []
];

echo json_encode($manifest, JSON_PRETTY_PRINT);
?>