/**
 * URL生成ヘルパ関数
 * @param {string} relativePath - 相対パス
 * @returns {string} 絶対URL
 */
function publicUrl(relativePath) {
  // 確実な文字列ベースURL生成
  console.log("publicUrl called with:", relativePath);
  
  // <meta name="app-base"> の内容を取得
  const meta = document.querySelector('meta[name="app-base"]')?.content?.trim();
  console.log("meta app-base:", meta);
  
  let base;
  
  if (meta) {
    // meta が設定されている場合はそれを使用
    base = meta.endsWith('/') ? meta : meta + '/';
  } else {
    // 現在のページから基準パスを生成
    base = location.origin + location.pathname.replace(/\/[^\/]*$/, '/');
  }
  
  console.log("base path:", base);
  
  // 相対パス記号を除去
  const cleanPath = relativePath.replace(/^\.\//, '');
  
  // 結合
  const result = base + cleanPath;
  console.log("final URL:", result);
  
  return result;
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