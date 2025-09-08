<?php
// 設定ファイル例
// config.php にコピーして設定してください
return [
  'pushover' => [
    'app_token' => 'azGDORePK8gMaC0QOYAMyEEuzJnyUi', // Pushoverアプリのトークン
    'user_key'  => 'uQiRzpo4DXghDmr9QzzfQu27cmVRsG', // ユーザーキー
  ],
  
  // === 初期セットアップ用（setup.php実行後は削除推奨）===
  'kids_setup' => [
    '太郎',
    '花子'
  ],
  
  // === setup.php の出力をここにコピーしてください ===
  'kids' => [
    // '12345678-1234-1234-1234-123456789abc' => '太郎',
    // '87654321-4321-4321-4321-ba9876543210' => '花子',
  ],
];