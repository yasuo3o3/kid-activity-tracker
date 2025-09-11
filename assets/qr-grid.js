// assets/qr-grid.js - PHP Server-side QR Code Generation
(function () {
  // ---- QR描画（1要素）- PHP Server側PNG生成 ----
  function generate(el, url, size) {
    try {
      if (!url || el.dataset.qrDone === '1') return;
      el.innerHTML = "";
      
      // Create image element (natural sizeで表示)
      var img = new Image();
      img.alt = url;
      
      // Generate QR code URL pointing to PHP endpoint (naturalSize=clientSize対策)
      var qrUrl = './assets/qr.php?text=' + encodeURIComponent(url) + '&s=264&ecc=H&m=4&exact=1';
      
      // Handle image load success
      img.onload = function() {
        el.appendChild(img);
        el.dataset.qrDone = '1';
      };
      
      // Handle image load error - fallback to link
      img.onerror = function() {
        console.error("QR generation error: Failed to load QR image");
        el.innerHTML =
          '<a href="' + String(url).replace(/"/g, '&quot;') +
          '" target="_blank" rel="noopener">' + String(url) + '</a>';
        el.dataset.qrDone = '1';
      };
      
      // Set image source (triggers loading)
      img.src = qrUrl;
      
    } catch (e) {
      console.error("QR generation error:", e);
      el.innerHTML =
        '<a href="' + String(url).replace(/"/g, '&quot;') +
        '" target="_blank" rel="noopener">' + String(url) + '</a>';
      el.dataset.qrDone = '1';
    }
  }

  // ---- スキャンして未処理だけ生成 + コピーbind ----
  function scan(root) {
    var scope = root || document;
    scope.querySelectorAll('.qr-canvas[data-url]').forEach(function (el) {
      var url  = el.getAttribute('data-url')  || '';
      var size = parseInt(el.getAttribute('data-size') || '220', 10);
      generate(el, url, size);
    });

    scope.querySelectorAll('.qr-copy[data-copy]').forEach(function (btn) {
      if (btn.dataset.copyBound === '1') return;
      btn.addEventListener('click', function () {
        var t = btn.getAttribute('data-copy') || '';
        navigator.clipboard.writeText(t).then(function () {
          var old = btn.textContent;
          btn.textContent = 'コピーしました';
          setTimeout(function(){ btn.textContent = old || 'リンクをコピー'; }, 1500);
        }).catch(function () { alert('コピーに失敗しました'); });
      });
      btn.dataset.copyBound = '1';
    });
  }

  // ---- 公開API（何度呼んでもOK） ----
  window.initQRGrid = function (root) { scan(root); };

  // ---- 初回実行 ----
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ scan(); });
  } else {
    scan();
  }

  // ---- 追加ノードも自動対応（親IDが無ければbody監視） ----
  var host = document.getElementById('kid-qr-grid') || document.body;
  try {
    var mo = new MutationObserver(function(muts){
      muts.forEach(function(m){
        m.addedNodes && m.addedNodes.forEach(function(n){
          if (n && n.nodeType === 1) scan(n);
        });
      });
    });
    mo.observe(host, { childList: true, subtree: true });
  } catch (_) {}
})();
