<?php
if (!defined('ABSPATH')) {
    exit;
}

if (isset($_POST['submit']) && wp_verify_nonce($_POST['kid_qr_grid_nonce'], 'kid_qr_grid_save')) {
    $api_url = sanitize_text_field($_POST['kid_qr_grid_api_url']);
    $pwa_base = sanitize_text_field($_POST['kid_qr_grid_pwa_base']);
    
    update_option('kid_qr_grid_api_url', esc_url_raw($api_url));
    update_option('kid_qr_grid_pwa_base', esc_url_raw($pwa_base));
    
    echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
}

$api_url = get_option('kid_qr_grid_api_url', '');
$pwa_base = get_option('kid_qr_grid_pwa_base', '');
?>

<div class="wrap">
    <h1>Kid QR Grid 設定</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('kid_qr_grid_save', 'kid_qr_grid_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="kid_qr_grid_api_url">子一覧APIエンドポイントURL</label>
                </th>
                <td>
                    <input type="url" 
                           id="kid_qr_grid_api_url" 
                           name="kid_qr_grid_api_url" 
                           value="<?php echo esc_attr($api_url); ?>" 
                           class="regular-text" 
                           placeholder="https://example.com/api/kids.json"
                           required />
                    <p class="description">
                        子ども情報を取得するAPIのURLを入力してください。<br>
                        APIは以下の形式でJSONを返す必要があります：<br>
                        <code>[{"id": "uuid", "display_name": "名前"}, ...]</code>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="kid_qr_grid_pwa_base">子ども用PWAベースURL</label>
                </th>
                <td>
                    <input type="url" 
                           id="kid_qr_grid_pwa_base" 
                           name="kid_qr_grid_pwa_base" 
                           value="<?php echo esc_attr($pwa_base); ?>" 
                           class="regular-text" 
                           placeholder="https://example.com/kid-activity-tracker/"
                           required />
                    <p class="description">
                        子ども用PWAのベースURLを入力してください。<br>
                        末尾に「?kid_id={子どものID}」が自動で追加されます。
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('設定を保存'); ?>
    </form>
    
    <hr>
    
    <h2>使用方法</h2>
    <p>設定を保存後、投稿やページに以下のショートコードを追加してください：</p>
    <p><code>[kid_qr_grid]</code></p>
    
    <h3>表示内容</h3>
    <ul>
        <li>各子どもの名前</li>
        <li>専用URLのQRコード</li>
        <li>リンクコピーボタン</li>
    </ul>
    
    <?php if (!empty($api_url) && !empty($pwa_base)): ?>
        <h3>プレビュー</h3>
        <p>設定が完了しています。ショートコードを配置したページで表示を確認してください。</p>
        
        <?php if (!empty($api_url)): ?>
            <p><strong>API URL:</strong> <code><?php echo esc_html($api_url); ?></code></p>
        <?php endif; ?>
        
        <?php if (!empty($pwa_base)): ?>
            <p><strong>PWA Base URL:</strong> <code><?php echo esc_html($pwa_base); ?></code></p>
            <p>例：<code><?php echo esc_html($pwa_base); ?>?kid_id=123e4567-e89b-12d3-a456-426614174000</code></p>
        <?php endif; ?>
    <?php else: ?>
        <div class="notice notice-warning">
            <p>QRコードを表示するには、上記の設定をすべて入力して保存してください。</p>
        </div>
    <?php endif; ?>
</div>