/**
 * URL生成ヘルパ関数
 * @param {string} relativePath - 相対パス
 * @returns {string} 絶対URL
 */
function publicUrl(relativePath) {
  // <meta name="app-base"> があればそれを優先
  const meta = document.querySelector('meta[name="app-base"]')?.content?.trim();
  const candidate = meta || document.baseURI || location.href;
  const absBase = new URL(candidate, location.href).href;
  return new URL(relativePath, absBase).href;
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