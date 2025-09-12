/**
 * URL生成ヘルパ関数
 * @param {string} relativePath - 相対パス
 * @returns {string} 絶対URL
 */
function publicUrl(relativePath) {
  // より堅牢なURL生成
  try {
    // <meta name="app-base"> があればそれを使用
    const meta = document.querySelector('meta[name="app-base"]')?.content?.trim();
    if (meta) {
      return new URL(relativePath, meta).href;
    }
    
    // document.baseURI を試す
    if (document.baseURI) {
      return new URL(relativePath, document.baseURI).href;
    }
    
    // 最終フォールバック: 現在のページのディレクトリベース
    const currentDir = location.origin + location.pathname.replace(/\/[^\/]*$/, '/');
    return new URL(relativePath, currentDir).href;
    
  } catch (e) {
    console.error("All URL construction methods failed:", e);
    // 最終フォールバック: 文字列結合
    const base = location.origin + location.pathname.replace(/\/[^\/]*$/, '/');
    const cleanPath = relativePath.replace(/^\.\//, '');
    const result = base + cleanPath;
    console.log("Using string concatenation fallback:", result);
    return result;
  }
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