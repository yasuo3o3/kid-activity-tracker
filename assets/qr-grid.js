(function () {
  'use strict';
  
  function generateQRCode(el, url, size) {
    try {
      if (!url) return;
      el.innerHTML = "";
      new QRCode(el, {
        text: url,
        width: size,
        height: size,
        correctLevel: QRCode.CorrectLevel.L
      });
    } catch (e) {
      console.error("QR generation error:", e);
      el.innerHTML = '<a href="'+ url.replace(/"/g,'&quot;') +'" target="_blank" rel="noopener">'+ url +'</a>';
    }
  }
  
  window.initQRGrid = function () {
    if (typeof QRCode === 'undefined') {
      console.warn('QRCode library not loaded');
      return;
    }
    
    document.querySelectorAll('.qr-canvas[data-url]').forEach(function (el) {
      var url  = el.getAttribute('data-url')  || '';
      var size = parseInt(el.getAttribute('data-size') || '220', 10);
      generateQRCode(el, url, size);
    });
    
    document.querySelectorAll('.qr-copy[data-copy]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var t = btn.getAttribute('data-copy') || '';
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(t).then(function(){
            var originalText = btn.textContent;
            btn.textContent = 'コピーしました';
            btn.disabled = true;
            setTimeout(function(){ 
              btn.textContent = originalText;
              btn.disabled = false;
            }, 1500);
          }).catch(function(){ 
            alert('コピーに失敗しました'); 
          });
        } else {
          // フォールバック方式
          var textArea = document.createElement('textarea');
          textArea.value = t;
          textArea.style.position = 'fixed';
          textArea.style.left = '-999999px';
          textArea.style.top = '-999999px';
          textArea.setAttribute('readonly', '');
          textArea.setAttribute('aria-hidden', 'true');
          
          document.body.appendChild(textArea);
          textArea.focus();
          textArea.select();
          
          try {
            var successful = document.execCommand('copy');
            var originalText = btn.textContent;
            btn.textContent = successful ? 'コピーしました' : 'コピーに失敗しました';
            btn.disabled = true;
            setTimeout(function(){ 
              btn.textContent = originalText;
              btn.disabled = false;
            }, 1500);
          } catch (error) {
            alert('コピーに失敗しました');
          } finally {
            document.body.removeChild(textArea);
          }
        }
      });
    });
  };
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initQRGrid);
  } else {
    window.initQRGrid();
  }
})();