<?php
/**
 * 高品質QRコードPNG生成エンドポイント
 * GET parameters:
 *   - text: QRコード化する文字列（必須）
 *   - s: 希望サイズpx（80-1024、既定180）
 *   - ecc: 誤り訂正レベル L|M|Q|H（既定Q）
 *   - m: 余白サイズ（1-8、既定2）
 *   - exact: CSS用完全サイズ合わせ（0|1、既定0）
 */

// ヘッダ送出前のバイト出力を防ぐ
ob_start();

// QRマスクパターン最適化（ストライプ対策）
if (!defined('QR_FIND_BEST_MASK'))   define('QR_FIND_BEST_MASK', true);
if (!defined('QR_FIND_FROM_RANDOM')) define('QR_FIND_FROM_RANDOM', false);
if (!defined('QR_DEFAULT_MASK'))     define('QR_DEFAULT_MASK', 2);
define('QR_LOG_DIR', __DIR__.'/qrlog'); // ログ確認用

// Include the QR code library
require_once __DIR__ . '/phpqrcode.php';

// セキュリティヘッダ
$security_headers = [
    'X-Content-Type-Options: nosniff',
    'X-Frame-Options: DENY', 
    'X-XSS-Protection: 1; mode=block',
    'Access-Control-Allow-Origin: *',
    'Access-Control-Allow-Methods: GET',
    'Access-Control-Allow-Headers: Content-Type'
];

function sendError($code, $message) {
    ob_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

// パラメータ取得と検証（naturalSize=clientSize対策）
$text = isset($_GET['text']) ? $_GET['text'] : '';
$s = isset($_GET['s']) ? intval($_GET['s']) : 264;
$ecc = isset($_GET['ecc']) ? strtoupper($_GET['ecc']) : 'H';
$m = isset($_GET['m']) ? intval($_GET['m']) : 4;
$exact = isset($_GET['exact']) ? intval($_GET['exact']) : 1;  // 最近傍リサイズ既定

// 入力検証
if (empty($text)) {
    sendError(400, 'Missing text parameter');
}

if (strlen($text) > 4096) {
    sendError(400, 'Text too long (max 4096 bytes)');
}

if ($s < 80 || $s > 1024) {
    $s = 180;
}

if (!in_array($ecc, ['L', 'M', 'Q', 'H'])) {
    $ecc = 'Q';
}

if ($m < 1 || $m > 8) {
    $m = 2;
}

// ECC変換
$ecc_map = [
    'L' => SimpleQRCode::ERROR_CORRECTION_L,
    'M' => SimpleQRCode::ERROR_CORRECTION_M, 
    'Q' => SimpleQRCode::ERROR_CORRECTION_Q,
    'H' => SimpleQRCode::ERROR_CORRECTION_H
];
$ecc_level = $ecc_map[$ecc];

try {
    // アルゴリズムA: 整数ドット計算
    $qr = new SimpleQRCode($text, $ecc_level);
    $matrix = $qr->generateMatrix();
    $modules = count($matrix);
    
    // point（matrixPointSize）を整数で決定
    // modules≈33を基準に計算、3-10の範囲でクランプ
    $point = max(3, min(10, round($s / 33)));
    
    // 実出力サイズ（natural size）
    $output_size = ($modules + 2 * $m) * $point;
    
    // ETag生成（キャッシュ制御）
    $etag_data = $text . '|' . $ecc . '|' . $m . '|' . $point . '|v2';
    $etag = '"' . md5($etag_data) . '"';
    
    // ETag検証（304対応）
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        ob_clean();
        http_response_code(304);
        foreach ($security_headers as $header) {
            header($header);
        }
        header('ETag: ' . $etag);
        exit;
    }
    
    if ($exact == 1) {
        // アルゴリズムB: サーバー側最近傍リサイズ（ボケ防止）
        // 大きなpointで生成してからIMG_NEAREST_NEIGHBOURで縮小
        $large_point = min(10, max(8, $point * 2));
        $large_size = ($modules + 2 * $m) * $large_point;
        
        // 大きいサイズで生成
        $large_img = generateOptimizedQRCodePNG($matrix, $large_point, $m);
        
        if (!$large_img) {
            throw new Exception('Failed to generate large QR code image');
        }
        
        // 最近傍リサイズでピクセル完璧維持
        if (function_exists('imagescale')) {
            // PHP 5.5+ の高品質リサイズ
            $img = imagescale($large_img, $s, $s, IMG_NEAREST_NEIGHBOUR);
            imagedestroy($large_img);
        } else {
            // fallback: imagecopyresized
            $img = imagecreatetruecolor($s, $s);
            $white = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $white);
            imagecopyresized($img, $large_img, 0, 0, 0, 0, $s, $s, $large_size, $large_size);
            imagedestroy($large_img);
        }
        
        if (!$img) {
            throw new Exception('Failed to resize QR code image');
        }
        
        $actual_size = $s;
    } else {
        // 通常生成（natural size）
        $img = generateOptimizedQRCodePNG($matrix, $point, $m);
        $actual_size = $output_size;
        
        if (!$img) {
            throw new Exception('Failed to generate QR code image');
        }
    }
    
    // ヘッダ送出
    ob_clean();
    foreach ($security_headers as $header) {
        header($header);
    }
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    header('ETag: ' . $etag);
    
    // PNG出力
    imagepng($img);
    imagedestroy($img);
    
} catch (Exception $e) {
    error_log('QR Code generation error: ' . $e->getMessage());
    sendError(500, 'QR code generation failed');
}

/**
 * 最適化されたQRコードPNG生成
 */
function generateOptimizedQRCodePNG($matrix, $point, $margin) {
    $modules = count($matrix);
    $size_with_margin = $modules + 2 * $margin;
    $img_size = $size_with_margin * $point;
    
    // 画像作成
    $img = imagecreate($img_size, $img_size);
    
    // 色定義
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    
    // 背景塗りつぶし
    imagefill($img, 0, 0, $white);
    
    // QRコード描画（余白考慮）
    for ($i = 0; $i < $modules; $i++) {
        for ($j = 0; $j < $modules; $j++) {
            if ($matrix[$i][$j] == 1) {
                $x1 = ($j + $margin) * $point;
                $y1 = ($i + $margin) * $point;
                $x2 = $x1 + $point - 1;
                $y2 = $y1 + $point - 1;
                
                imagefilledrectangle($img, $x1, $y1, $x2, $y2, $black);
            }
        }
    }
    
    return $img;
}
?>