/**
 * Link Copy Functionality
 * Handles clipboard copying for .qr-copy[data-copy] elements
 */
(function () {
  'use strict';

  // Event delegation for copy buttons
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.qr-copy[data-copy]');
    if (!btn) return;
    
    const text = btn.getAttribute('data-copy') || '';
    if (!text) {
      console.warn('No data-copy attribute found');
      return;
    }

    // Copy to clipboard
    const copyPromise = navigator.clipboard 
      ? navigator.clipboard.writeText(text)
      : Promise.reject(new Error('Clipboard API not supported'));
    
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
        // SweetAlert2が利用可能な場合は使用、そうでなければalert
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'エラー',
            text: 'コピーに失敗しました',
            confirmButtonText: 'OK'
          });
        } else {
          alert('コピーに失敗しました');
        }
      });
  });

})();