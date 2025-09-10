document.addEventListener('DOMContentLoaded', function() {
    const qrGrids = document.querySelectorAll('.kid-qr-grid');
    
    qrGrids.forEach(function(grid) {
        initializeQRGrid(grid);
    });
    
    function initializeQRGrid(grid) {
        const qrElements = grid.querySelectorAll('.kid-qr');
        const copyButtons = grid.querySelectorAll('.kid-copy-btn');
        
        qrElements.forEach(function(element) {
            generateQRCode(element);
        });
        
        copyButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                copyToClipboard(button);
            });
        });
    }
    
    function generateQRCode(element) {
        const url = element.getAttribute('data-url');
        if (!url) {
            console.error('QR element missing data-url attribute');
            return;
        }
        
        const canvas = document.createElement('canvas');
        canvas.width = 128;
        canvas.height = 128;
        
        element.innerHTML = '';
        element.appendChild(canvas);
        
        try {
            if (typeof QRCode !== 'undefined') {
                const qr = new QRCode({
                    text: url,
                    width: 128,
                    height: 128,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
                
                qr.toCanvas(canvas, {
                    colorDark: '#000000',
                    colorLight: '#ffffff'
                });
            } else {
                displayQRFallback(element, url);
            }
        } catch (error) {
            console.error('QR generation error:', error);
            displayQRFallback(element, url);
        }
    }
    
    function displayQRFallback(element, url) {
        element.innerHTML = '<div class="qr-fallback">QRコード<br>生成中...</div>';
        
        setTimeout(function() {
            if (typeof QRCode !== 'undefined') {
                generateQRCode(element);
            } else {
                element.innerHTML = '<div class="qr-error">QRコードを<br>生成できません</div>';
            }
        }, 1000);
    }
    
    function copyToClipboard(button) {
        const url = button.getAttribute('data-url');
        if (!url) {
            showCopyResult(button, false, 'URLが見つかりません');
            return;
        }
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url)
                .then(function() {
                    showCopyResult(button, true, 'コピーしました');
                })
                .catch(function(error) {
                    console.error('Clipboard API error:', error);
                    fallbackCopyToClipboard(url, button);
                });
        } else {
            fallbackCopyToClipboard(url, button);
        }
    }
    
    function fallbackCopyToClipboard(text, button) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            showCopyResult(button, successful, successful ? 'コピーしました' : 'コピーに失敗しました');
        } catch (error) {
            console.error('Fallback copy error:', error);
            showCopyResult(button, false, 'コピーに失敗しました');
        } finally {
            document.body.removeChild(textArea);
        }
    }
    
    function showCopyResult(button, success, message) {
        const originalText = button.textContent;
        button.textContent = message;
        button.classList.add(success ? 'copy-success' : 'copy-error');
        
        setTimeout(function() {
            button.textContent = originalText;
            button.classList.remove('copy-success', 'copy-error');
        }, 2000);
    }
});