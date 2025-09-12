/**
 * URL生成ヘルパ関数
 * @param {string} relativePath - 相対パス
 * @returns {string} 絶対URL
 */
function publicUrl(relativePath) {
  // <meta name="app-base"> があればそれを優先
  const appBaseMeta = document.querySelector('meta[name="app-base"]');
  if (appBaseMeta) {
    return new URL(relativePath, appBaseMeta.getAttribute('content')).href;
  }
  
  // なければ document.baseURI を使用
  return new URL(relativePath, document.baseURI).href;
}

/**
 * 子どものリンクURL生成
 * @param {string} kidId - 子どもID
 * @returns {string} 子どもページの絶対URL
 */
function getKidUrl(kidId) {
  return publicUrl('./?kid=' + encodeURIComponent(kidId));
}

/**
 * API エンドポイントURL生成
 * @param {string} endpoint - APIエンドポイント (例: 'api/switch.php')
 * @returns {string} APIの絶対URL
 */
function getApiUrl(endpoint) {
  return publicUrl('./' + endpoint);
}