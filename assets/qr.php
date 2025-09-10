<?php
/**
 * QR Code PNG Generation Endpoint
 * GET parameters:
 *   - text: String to encode in QR code
 *   - s: Size in pixels (80-1024, default: 180)
 */

// Include the QR code library
require_once __DIR__ . '/phpqrcode.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers (adjust as needed for your domain)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Cache headers for long-term caching
header('Cache-Control: public, max-age=31536000, immutable');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

// Validate and sanitize input
$text = isset($_GET['text']) ? $_GET['text'] : '';
$size = isset($_GET['s']) ? intval($_GET['s']) : 180;

// Input validation
if (empty($text)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing text parameter']);
    exit;
}

// Size validation
if ($size < 80 || $size > 1024) {
    $size = 180; // Default to safe size
}

// Limit text length to prevent abuse
if (strlen($text) > 1000) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Text too long (max 1000 characters)']);
    exit;
}

// Sanitize text to prevent XSS
$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

try {
    // Generate QR code image
    $img = generateQRCodePNG($text, $size, SimpleQRCode::ERROR_CORRECTION_L);
    
    if (!$img) {
        throw new Exception('Failed to generate QR code image');
    }
    
    // Set content type for PNG
    header('Content-Type: image/png');
    
    // Output the image
    imagepng($img);
    
    // Clean up memory
    imagedestroy($img);
    
} catch (Exception $e) {
    // Log error (in production, use proper logging)
    error_log('QR Code generation error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'QR code generation failed']);
    exit;
}
?>