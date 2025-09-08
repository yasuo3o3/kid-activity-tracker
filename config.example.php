<?php
// Pushover設定例
// config.php にコピーして設定してください
return [
  'pushover' => [
    'app_token' => 'azGDORePK8gMaC0QOYAMyEEuzJnyUi', // Pushoverアプリのトークン
    'user_key'  => 'uQiRzpo4DXghDmr9QzzfQu27cmVRsG', // ユーザーキー
  ],
  
  // 子どもの表示名マッピング（通知用）
  'kids' => [
    '12345678-1234-1234-1234-123456789abc' => 'やすお君',
    '87654321-4321-4321-4321-ba9876543210' => 'みさきちゃん',
  ],
];