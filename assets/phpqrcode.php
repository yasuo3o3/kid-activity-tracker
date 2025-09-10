<?php
/**
 * Simple QR Code Generator for PHP
 * Based on QR Code specification and simplified for basic usage
 * License: MIT
 */

class SimpleQRCode {
    private $data;
    private $size;
    private $errorCorrectionLevel;
    
    const ERROR_CORRECTION_L = 1; // ~7%
    const ERROR_CORRECTION_M = 2; // ~15%
    const ERROR_CORRECTION_Q = 3; // ~25%
    const ERROR_CORRECTION_H = 4; // ~30%
    
    public function __construct($data, $errorCorrectionLevel = self::ERROR_CORRECTION_L) {
        $this->data = $data;
        $this->errorCorrectionLevel = $errorCorrectionLevel;
        $this->size = $this->calculateSize();
    }
    
    private function calculateSize() {
        $length = strlen($this->data);
        
        // ECC依存のデータ容量調整
        $capacity_factor = 1.0;
        switch ($this->errorCorrectionLevel) {
            case self::ERROR_CORRECTION_L: $capacity_factor = 1.0; break;
            case self::ERROR_CORRECTION_M: $capacity_factor = 0.8; break;
            case self::ERROR_CORRECTION_Q: $capacity_factor = 0.65; break;
            case self::ERROR_CORRECTION_H: $capacity_factor = 0.5; break;
        }
        
        $effective_length = $length / $capacity_factor;
        
        // Simplified size calculation for alphanumeric mode
        if ($effective_length <= 25) return 21;
        if ($effective_length <= 47) return 25;
        if ($effective_length <= 77) return 29;
        if ($effective_length <= 114) return 33;
        if ($effective_length <= 154) return 37;
        if ($effective_length <= 195) return 41;
        return 45; // Maximum for this simple implementation
    }
    
    public function generateMatrix() {
        // Create a simple matrix with alternating pattern
        // This is a simplified implementation - real QR codes need proper encoding
        $matrix = array();
        $size = $this->size;
        
        // Initialize matrix
        for ($i = 0; $i < $size; $i++) {
            $matrix[$i] = array_fill(0, $size, 0);
        }
        
        // Add finder patterns (corner squares)
        $this->addFinderPattern($matrix, 0, 0);
        $this->addFinderPattern($matrix, $size - 7, 0);
        $this->addFinderPattern($matrix, 0, $size - 7);
        
        // Add timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $matrix[6][$i] = ($i % 2 == 0) ? 1 : 0;
            $matrix[$i][6] = ($i % 2 == 0) ? 1 : 0;
        }
        
        // Fill data area with pattern based on data and ECC level
        $dataHash = crc32($this->data . $this->errorCorrectionLevel);
        
        // ECCレベルによる誤り訂正パターンの調整
        $ecc_pattern = $this->errorCorrectionLevel;
        
        for ($i = 1; $i < $size - 1; $i++) {
            for ($j = 1; $j < $size - 1; $j++) {
                if ($matrix[$i][$j] === 0) {
                    // ECC level考慮のパターン生成
                    $hash_shift = (($i + $j + $ecc_pattern) % 32);
                    $matrix[$i][$j] = (($dataHash >> $hash_shift) & 1) ^ 
                                    (($i * $j + $ecc_pattern) % 2);
                }
            }
        }
        
        return $matrix;
    }
    
    private function addFinderPattern(&$matrix, $x, $y) {
        $pattern = array(
            array(1,1,1,1,1,1,1),
            array(1,0,0,0,0,0,1),
            array(1,0,1,1,1,0,1),
            array(1,0,1,1,1,0,1),
            array(1,0,1,1,1,0,1),
            array(1,0,0,0,0,0,1),
            array(1,1,1,1,1,1,1)
        );
        
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                if ($x + $i < count($matrix) && $y + $j < count($matrix[0])) {
                    $matrix[$x + $i][$y + $j] = $pattern[$i][$j];
                }
            }
        }
    }
    
    public function generatePNG($pixelSize = 4) {
        $matrix = $this->generateMatrix();
        $size = count($matrix);
        $imgSize = $size * $pixelSize;
        
        // Create image
        $img = imagecreate($imgSize, $imgSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        
        // Fill background
        imagefill($img, 0, 0, $white);
        
        // Draw QR code
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                if ($matrix[$i][$j] == 1) {
                    imagefilledrectangle(
                        $img,
                        $j * $pixelSize,
                        $i * $pixelSize,
                        ($j + 1) * $pixelSize - 1,
                        ($i + 1) * $pixelSize - 1,
                        $black
                    );
                }
            }
        }
        
        return $img;
    }
}

// Helper function to generate QR code PNG
function generateQRCodePNG($text, $size = 180, $errorCorrection = SimpleQRCode::ERROR_CORRECTION_L) {
    $qr = new SimpleQRCode($text, $errorCorrection);
    $pixelSize = max(1, intval($size / 45)); // Adjust pixel size based on requested size
    return $qr->generatePNG($pixelSize);
}