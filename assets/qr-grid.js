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
      // QRコード生成（複数の設定で試行）
      let qr = null;
      
      // typeNumberと誤り訂正レベルの組み合わせを試行
      const configs = [
        { type: 10, level: QRCode.CorrectLevel.M },
        { type: 8, level: QRCode.CorrectLevel.M },
        { type: 6, level: QRCode.CorrectLevel.M },
        { type: 4, level: QRCode.CorrectLevel.M },
        { type: 10, level: QRCode.CorrectLevel.L },
        { type: 8, level: QRCode.CorrectLevel.L },
        { type: 6, level: QRCode.CorrectLevel.L },
        { type: 4, level: QRCode.CorrectLevel.L }
      ];
      
      for (const config of configs) {
        try {
          qr = new QRCode();
          qr.text = url;
          qr.size = 200;
          qr.colorDark = '#000000';
          qr.colorLight = '#ffffff';
          qr.correctLevel = config.level;
          qr.typeNumber = config.type;
          
          qr.addData(url);
          qr.make();
          console.log(`QR generated successfully with type:${config.type}, level:${config.level}`);
          break; // 成功したらループ終了
        } catch (e) {
          console.warn(`QR type:${config.type}, level:${config.level} failed:`, e.message);
          qr = null;
        }
      }
      
      if (qr) {
        qr.toCanvas(canvas);
      } else {
        throw new Error('All QR configurations failed');
      }
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