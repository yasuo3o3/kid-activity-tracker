/**
 * Link Copy Functionality
 * Handles clipboard copying for .qr-copy[data-copy] elements
 */
(function () {
  'use strict';

  // Copy text to clipboard with fallback
  function copyTextToClipboard(text) {
    // Modern Clipboard API (requires HTTPS)
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    
    // Fallback for older browsers or HTTP
    return new Promise((resolve, reject) => {
      const textArea = document.createElement('textarea');
      textArea.value = text;
      textArea.style.position = 'fixed';
      textArea.style.left = '-999999px';
      textArea.style.top = '-999999px';
      document.body.appendChild(textArea);
      
      try {
        textArea.focus();
        textArea.select();
        const successful = document.execCommand('copy');
        document.body.removeChild(textArea);
        
        if (successful) {
          resolve();
        } else {
          reject(new Error('document.execCommand failed'));
        }
      } catch (err) {
        document.body.removeChild(textArea);
        reject(err);
      }
    });
  }

  // Event delegation for copy buttons
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.qr-copy[data-copy]');
    if (!btn) return;
    
    const text = btn.getAttribute('data-copy') || '';
    if (!text) {
      console.warn('No data-copy attribute found');
      return;
    }

    // Copy to clipboard with fallback
    const copyPromise = copyTextToClipboard(text);
    
    copyPromise
      .then(() => {
        // Success feedback
        const originalText = btn.textContent;
        btn.textContent = 'コピーしました';
        
        // Reset text after 1.5 seconds
        setTimeout(() => {
          btn.textContent = originalText || 'リンクをコピー';
        }, 1500);
      })
      .catch((error) => {
        console.error('Copy failed:', error);
        alert('コピーに失敗しました');
      });
  });

})();