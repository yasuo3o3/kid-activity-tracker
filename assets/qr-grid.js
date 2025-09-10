(function() {
  'use strict';
  
  // QRグリッド初期化関数をグローバルに公開
  window.initQRGrid = function() {
    if (typeof QRCode === 'undefined') {
      console.warn('QRCode library not loaded');
      return;
    }
    
    const qrCanvases = document.querySelectorAll('.qr-canvas[data-url]');
    const copyButtons = document.querySelectorAll('.qr-copy[data-copy]');
    
    // QRコード生成
    qrCanvases.forEach(function(canvas) {
      generateQRCode(canvas);
    });
    
    // コピーボタンのイベント設定
    copyButtons.forEach(function(button) {
      button.addEventListener('click', function() {
        copyToClipboard(button);
      });
    });
  };
  
  function generateQRCode(element) {
    const url = element.getAttribute('data-url');
    if (!url) {
      console.error('QR element missing data-url attribute');
      return;
    }
    
    // 既存の内容をクリア
    element.innerHTML = '';
    
    // Canvas要素を作成
    const canvas = document.createElement('canvas');
    canvas.width = 200;
    canvas.height = 200;
    canvas.style.maxWidth = '100%';
    canvas.style.height = 'auto';
    
    element.appendChild(canvas);
    
    try {
      // QRコード生成（誤り訂正レベルを最大容量のLに設定）
      const qr = new QRCode(url, {
        size: 200,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.L  // コンストラクタで直接指定
      });
      
      qr.toCanvas(canvas);
    } catch (error) {
      console.error('QR generation error:', error);
      showQRError(element, url);
    }
  }
  
  function showQRError(element, url) {
    element.innerHTML = '<div class="qr-error">QRコード生成エラー<br><small>' + 
                       escapeHtml(url.substring(0, 50)) + 
                       (url.length > 50 ? '...' : '') + 
                       '</small></div>';
  }
  
  function copyToClipboard(button) {
    const url = button.getAttribute('data-copy');
    if (!url) {
      showCopyResult(button, false, 'URLが見つかりません');
      return;
    }
    
    // 現代のブラウザのClipboard API
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
      // フォールバック方式
      fallbackCopyToClipboard(url, button);
    }
  }
  
  function fallbackCopyToClipboard(text, button) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    textArea.setAttribute('readonly', '');
    textArea.setAttribute('aria-hidden', 'true');
    
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
    const originalClass = button.className;
    
    button.textContent = message;
    button.className = originalClass + (success ? ' qr-copy-success' : ' qr-copy-error');
    button.disabled = true;
    
    setTimeout(function() {
      button.textContent = originalText;
      button.className = originalClass;
      button.disabled = false;
    }, 2000);
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }
  
  // DOMContentLoaded後の自動初期化（既存のQRグリッドがあれば）
  document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('#kid-qr-grid')) {
      // ライブラリの読み込み待機
      const checkQRLibrary = setInterval(function() {
        if (typeof QRCode !== 'undefined') {
          window.initQRGrid();
          clearInterval(checkQRLibrary);
        }
      }, 100);
      
      // 5秒でタイムアウト
      setTimeout(function() {
        clearInterval(checkQRLibrary);
      }, 5000);
    }
  });
  
})();